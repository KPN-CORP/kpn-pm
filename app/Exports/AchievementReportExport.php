<?php

namespace App\Exports;

use App\Models\Goal;
use App\Services\KPIAchievementService;
use App\Services\KPIService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AchievementReportExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, WithEvents
{
    protected $groupCompany, $location, $company, $period;
    protected $permissionLocations, $permissionCompanies, $permissionGroupCompanies;
    protected $options = [];
    protected $path;

    public function __construct(
        $groupCompany, $location, $company, $period,
        $permissionLocations = [], $permissionCompanies = [], $permissionGroupCompanies = []
    ) {
        $this->groupCompany             = $groupCompany;
        $this->location                 = $location;
        $this->company                  = $company;
        $this->period                   = $period;
        $this->permissionLocations      = $permissionLocations;
        $this->permissionCompanies      = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
        $this->path                     = base_path('resources/goal.json');

        if (File::exists($this->path)) {
            $this->options = json_decode(File::get($this->path), true) ?? [];
        }
    }

    public function query()
    {
        $query = Goal::with('employee')
            ->where('period', $this->period ?? date('Y'))
            ->whereHas('achievement');

        $query->whereHas('employee', function ($q) {
            if (!empty($this->permissionGroupCompanies)) $q->whereIn('group_company', $this->permissionGroupCompanies);
            if (!empty($this->permissionCompanies)) $q->whereIn('contribution_level_code', $this->permissionCompanies);
            if (!empty($this->permissionLocations)) $q->whereIn('work_area_code', $this->permissionLocations);
            if (!empty($this->groupCompany)) $q->whereIn('group_company', $this->groupCompany);
            if (!empty($this->location)) $q->whereIn('work_area_code', $this->location);
            if (!empty($this->company)) $q->whereIn('contribution_level_code', $this->company);
        });

        return $query->select('id', 'employee_id', 'form_data');
    }

    public function map($item): array
    {
        $rows = [];
        $formData = json_decode($item->form_data, true) ?? [];
        $achievementData = KPIAchievementService::getByGoal($item->id) ?? [];
        $isEmptyAchievement = empty($achievementData);

        $reviewPeriodMap = collect($this->options['Review Period'] ?? [])->flatten(1)->pluck('label', 'value')->toArray();
        $calculationMethodMap = collect($this->options['Calculation Method'] ?? [])->flatten(1)->pluck('label', 'value')->toArray();

        foreach ($formData as $kpiIndex => $kpi) {
            $kpiId = $kpi['kpi_id'] ?? null;
            $ach = $kpiId && isset($achievementData[$kpiId]['ach']) ? $achievementData[$kpiId]['ach'] : array_fill(1, 12, null);
            $values = collect($ach)->filter(fn($v) => $v !== null && $v !== '')->values()->toArray();

            $actual = app(KPIService::class)->aggregate($kpi['calculation_method'] ?? 'last', $values, $kpi['review_period'] ?? null);
            $achievement = $isEmptyAchievement ? 0 : app(KPIService::class)->achievement($actual, (float)($kpi['target'] ?? 0), $kpi['type'] ?? 'Higher Better');

            $reviewLabel = $reviewPeriodMap[$kpi['review_period'] ?? ''] ?? '-';
            $calcLabel   = $calculationMethodMap[$kpi['calculation_method'] ?? ''] ?? '-';

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
                $reviewLabel, // Label ini akan kita baca lagi di AfterSheet
                $calcLabel,
                $ach[1] ?? '', $ach[2] ?? '', $ach[3] ?? '', $ach[4] ?? '', $ach[5] ?? '', $ach[6] ?? '',
                $ach[7] ?? '', $ach[8] ?? '', $ach[9] ?? '', $ach[10] ?? '', $ach[11] ?? '', $ach[12] ?? '',
                round($actual, 2),
                round($achievement, 2),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['No', 'Employee ID', 'Employee Name', 'KPI', 'KPI Descriptions', 'Annual Target', 'UoM', 'Weight (%)', 'Type', 'Review Period', 'Calculation Method', 'Achievement', '', '', '', '', '', '', '', '', '', '', '', 'Total Achievement', 'Achievement (%)'],
            ['', '', '', '', '', '', '', '', '', '', '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', '', '']
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn(); 

                $range = 'A1:' . $lastColumn . $lastRow;
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $sheet->getStyle("L3:Y{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $sheet->getStyle("F3:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // Merge headers
                foreach (range('A', 'K') as $col) { $sheet->mergeCells($col.'1:'.$col.'2'); }
                $sheet->mergeCells('L1:W1');
                $sheet->mergeCells('X1:X2');
                $sheet->mergeCells('Y1:Y2');

                $sheet->getStyle('A1:Y2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                // âœ… LOGIKA BARU: Loop langsung ke baris Excel, tidak butuh Cache!
                for ($row = 3; $row <= $lastRow; $row++) {
                    // Ambil tulisan label (contoh: "Monthly", "Quarterly") dari kolom J
                    $reviewLabel = strtolower(trim($sheet->getCell("J{$row}")->getValue() ?? ''));
                    $activeMonths = $this->getActiveMonthsFromLabel($reviewLabel);

                    for ($col = 12; $col <= 23; $col++) {
                        $colString = Coordinate::stringFromColumnIndex($col);
                        $cell = $colString . $row;
                        $monthIndex = $col - 11;

                        if (!in_array($monthIndex, $activeMonths)) {
                            $sheet->getStyle($cell)->getFill()->applyFromArray([
                                'fillType'   => 'solid',
                                'startColor' => ['rgb' => 'D9D9D9'],
                            ]);
                        } else {
                            $sheet->getStyle($cell)->getFill()->applyFromArray([
                                'fillType'   => 'solid',
                                'startColor' => ['rgb' => 'FFFFFF'],
                            ]);
                        }
                    }
                }

            }
        ];
    }

    // âœ… Helpes diganti membaca Label langsung
    private function getActiveMonthsFromLabel(string $label): array
    {
        return match ($label) {
            'monthly', '1'    => range(1, 12),
            'bi-monthly', '2' => [2, 4, 6, 8, 10, 12],
            'quarterly', '3'  => [3, 6, 9, 12],
            'semester', '6'   => [6, 12],
            default           => range(1, 12),
        };
    }

    public function chunkSize(): int
    {
        return 100;
    }
}