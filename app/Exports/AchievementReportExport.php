<?php

namespace App\Exports;

use App\Models\Goal;
use App\Services\KPIAchievementService;
use App\Services\KPIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AchievementReportExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, ShouldQueue, WithEvents
{
    protected $groupCompany, $location, $company, $period;
    protected $permissionLocations, $permissionCompanies, $permissionGroupCompanies;
    protected $options = [];
    protected $path;
    protected $cacheKey; // ✅ key unik per export job
    protected $currentRow = 2;

    public function __construct(
        $groupCompany,
        $location,
        $company,
        $period,
        $permissionLocations = [],
        $permissionCompanies = [],
        $permissionGroupCompanies = []
    ) {
        $this->groupCompany             = $groupCompany;
        $this->location                 = $location;
        $this->company                  = $company;
        $this->period                   = $period;
        $this->permissionLocations      = $permissionLocations;
        $this->permissionCompanies      = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
        $this->path                     = base_path('resources/goal.json');

        // ✅ Key unik agar tidak tabrakan antar job
        $this->cacheKey = 'achievement_export_rows_' . uniqid();

        // ✅ Baca options sekali di constructor
        if (File::exists($this->path)) {
            $this->options = json_decode(File::get($this->path), true) ?? [];
        }
    }

    public function query()
    {
        $query = Goal::with('employee')
            ->where('period', $this->period ?? date('Y'));

        $query->whereHas('employee', function ($q) {
            if (!empty($this->permissionGroupCompanies)) {
                $q->whereIn('group_company', $this->permissionGroupCompanies);
            }
            if (!empty($this->permissionCompanies)) {
                $q->whereIn('contribution_level_code', $this->permissionCompanies);
            }
            if (!empty($this->permissionLocations)) {
                $q->whereIn('work_area_code', $this->permissionLocations);
            }
            if (!empty($this->groupCompany)) {
                $q->whereIn('group_company', $this->groupCompany);
            }
            if (!empty($this->location)) {
                $q->whereIn('work_area_code', $this->location);
            }
            if (!empty($this->company)) {
                $q->whereIn('contribution_level_code', $this->company);
            }
        });

        return $query->select('id', 'employee_id', 'form_data');
    }

    public function map($item): array
    {
        $rows           = [];
        $formData       = json_decode($item->form_data, true) ?? [];
        $achievementData = KPIAchievementService::getByGoal($item->id) ?? [];

        $reviewPeriodMap = collect($this->options['Review Period'] ?? [])
            ->flatten(1)
            ->pluck('label', 'value')
            ->toArray();

        $calculationMethodMap = collect($this->options['Calculation Method'] ?? [])
            ->flatten(1)
            ->pluck('label', 'value')
            ->toArray();

        foreach ($formData as $kpiIndex => $kpi) {

            $kpiId = $kpi['kpi_id'] ?? null;

            $ach = $kpiId && isset($achievementData[$kpiId]['ach'])
                ? $achievementData[$kpiId]['ach']
                : array_fill(1, 12, null);

            $values = collect($ach)
                ->filter(fn($v) => $v !== null && $v !== '')
                ->values()
                ->toArray();

            $actual = app(KPIService::class)->aggregate(
                $kpi['calculation_method'] ?? 'last',
                $values
            );

            $achievement = app(KPIService::class)->achievement(
                $actual,
                (float)($kpi['target'] ?? 0),
                $kpi['type'] ?? 'Higher Better'
            );

            $reviewLabel = $reviewPeriodMap[$kpi['review_period'] ?? ''] ?? '-';
            $calcLabel   = $calculationMethodMap[$kpi['calculation_method'] ?? ''] ?? '-';

            // ✅ Increment dulu, lalu simpan review_period row ini ke Cache
            $this->currentRow++;
            $reviewPeriod = $kpi['review_period'] ?? null;

            // ✅ Append ke cache (baca → tambah → simpan)
            $existing = Cache::get($this->cacheKey, []);
            $existing[$this->currentRow] = $reviewPeriod;
            Cache::put($this->cacheKey, $existing, now()->addHours(1));

            $rows[] = [
                $kpiIndex + 1,
                $item->employee->employee_id ?? '',
                $item->employee->fullname    ?? '',
                $kpi['kpi']         ?? '',
                $kpi['description'] ?? '',
                $kpi['target']      ?? 0,
                $kpi['uom']         ?? '',
                $kpi['weightage']   ?? 0,
                $kpi['type']        ?? '',
                $reviewLabel,
                $calcLabel,
                $ach[1]  ?? '',
                $ach[2]  ?? '',
                $ach[3]  ?? '',
                $ach[4]  ?? '',
                $ach[5]  ?? '',
                $ach[6]  ?? '',
                $ach[7]  ?? '',
                $ach[8]  ?? '',
                $ach[9]  ?? '',
                $ach[10] ?? '',
                $ach[11] ?? '',
                $ach[12] ?? '',
                round($actual, 2),
                round($achievement, 2),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            [
                'No', 'Employee ID', 'Employee Name',
                'KPI', 'KPI Descriptions', 'Annual Target', 'UoM', 'Weight (%)',
                'Type', 'Tracking Period', 'Achievement Calculation Method',
                'Achievement', '', '', '', '', '', '', '', '', '', '', '',
                'Total Achievement', 'Achievement (%)'
            ],
            [
                '', '', '',
                '', '', '', '', '',
                '', '', '',
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
                '',''
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn(); // contoh: X

                $range = 'A1:' . $lastColumn . $lastRow;

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                        ],
                    ],
                ]);

                // Merge header utama (vertical)
                foreach (range('A', 'K') as $col) {
                    $sheet->mergeCells($col.'1:'.$col.'2');
                }

                // Merge Achievement (horizontal)
                $sheet->mergeCells('L1:W1');

                // Merge Total Achievement
                $sheet->mergeCells('X1:X2');
                $sheet->mergeCells('Y1:Y2');

                $sheet->getStyle('A1:Y2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // ✅ Baca dari Cache, bukan dari property
                $rowReviewPeriods = Cache::get($this->cacheKey, []);

                Log::info('🎨 Styling Started', [
                    'total_rows' => count($rowReviewPeriods)
                ]);

                foreach ($rowReviewPeriods as $row => $reviewPeriod) {

                    $activeMonths = $this->getActiveMonths($reviewPeriod);

                    for ($col = 12; $col <= 23; $col++) {

                        $cell        = $sheet->getCellByColumnAndRow($col, $row)->getCoordinate();
                        $monthIndex  = $col - 11; // col 12 → bulan 1 (Jan)

                        if (!in_array($monthIndex, $activeMonths)) {
                            // ❌ Di luar review period → abu-abu
                            $sheet->getStyle($cell)
                                ->getFill()
                                ->applyFromArray([
                                    'fillType'   => 'solid',
                                    'startColor' => ['rgb' => 'D9D9D9'],
                                ]);
                        } else {
                            // ✅ Dalam review period → putih
                            $sheet->getStyle($cell)
                                ->getFill()
                                ->applyFromArray([
                                    'fillType'   => 'solid',
                                    'startColor' => ['rgb' => 'FFFFFF'],
                                ]);
                        }
                    }

                    // Border seluruh range bulan per row
                    $sheet->getStyle("L{$row}:W{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle('thin');
                }

                // ✅ Hapus cache setelah styling selesai
                Cache::forget($this->cacheKey);

                Log::info('🎨 Styling Finished');
            }
        ];
    }

    private function getActiveMonths($reviewPeriod): array
    {
        return match ((int) $reviewPeriod) {
            1   => range(1, 12),
            2   => [2, 4, 6, 8, 10, 12],
            3   => [3, 6, 9, 12],
            6   => [6, 12],
            default => range(1, 12),
        };
    }

    public function chunkSize(): int
    {
        return 100;
    }
}