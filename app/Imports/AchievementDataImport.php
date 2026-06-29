<?php

namespace App\Imports;

use App\Models\AchievementImportTransaction;
use App\Models\ApprovalLayer;
use App\Models\Goal;
use App\Models\KPIAchievement;
use App\Services\KPIAchievementSnapshotService;
use App\Services\KPIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AchievementDataImport implements ToCollection, WithHeadingRow, WithStartRow
{
    public function headingRow(): int
    {
        return 2;
    }

    public function startRow(): int
    {
        return 3;
    }

    protected string $filePath;
    protected string $type;
    protected string $period;
    protected array $rows = [];
    protected array $transactions = [];

    protected KPIService $kpiService;

    protected array $allowedEmployeeIds = [];

    public function __construct(string $filePath, string $period, ?string $type = null)
    {
        $this->filePath = str_replace('public/', '', $filePath);
        $this->type = $type ?: 'admin';
        $this->period = $period;
        $this->kpiService = app(KPIService::class);

        $currentUser = Auth::user()->employee_id;

        if ($this->type === 'self') {

            $this->allowedEmployeeIds = [
                $currentUser
            ];

        } elseif ($this->type === 'team') {

            $this->allowedEmployeeIds =
                ApprovalLayer::query()
                ->where(
                    'approver_id',
                    $currentUser
                )
                ->where(
                    'layer',
                    1
                )
                ->pluck('employee_id')
                ->toArray();
        }
    }

    /**
     * Read excel rows
     */
    public function collection(Collection $collection)
{
    foreach ($collection as $index => $row) {
        $employeeId = trim($row['employee_id'] ?? '');
        $kpiName    = trim($row['kpi'] ?? '');

        if ($this->type !== 'admin') {
            if (!in_array($employeeId, $this->allowedEmployeeIds)) {
                $this->transactions[] = [
                    'status'      => 'ERROR',
                    'employee_id' => $employeeId,
                    'kpi'         => $kpiName,
                    'message'     => "Employee ID {$employeeId} not allowed for {$this->type} import",
                ];
                continue;
            }
        }

        if (empty($employeeId) || empty($kpiName)) {
            continue;
        }

        $goal = Goal::where('employee_id', $employeeId)
            ->where('period', $this->period)
            ->first();

        if (!$goal) {
            continue;
        }

        $formData = is_array($goal->form_data)
            ? $goal->form_data
            : json_decode($goal->form_data, true);

        if (!$formData) {
            continue;
        }

        // Ambil nilai dari Excel untuk matching
        $excelKpi        = Str::of($kpiName)->replaceMatches('/\s+/', ' ')->trim()->lower()->value();
        $excelPeriod     = $this->mapReviewPeriod(trim(strtolower($row['review_period'] ?? '')));
        $excelCalcMethod = $this->normalizeCalcMethod(trim(strtolower($row['calculation_method'] ?? '')));
        $excelWeightage  = trim((string)($row['weightage'] ?? ''));
        $excelType       = trim(strtolower($row['type'] ?? ''));

        $kpiIndex    = null;
        $kpi         = null;
        $reviewPeriod = 1;

        foreach ($formData as $indexForm => $item) {
            $goalKpi        = Str::of($item['kpi'] ?? '')->replaceMatches('/\s+/', ' ')->trim()->lower()->value();
            $goalPeriod     = $this->mapReviewPeriod(trim(strtolower($item['review_period'] ?? '')));
            $goalCalcMethod = $this->normalizeCalcMethod(trim(strtolower($item['calculation_method'] ?? '')));
            $goalWeightage  = trim((string)($item['weightage'] ?? ''));
            $goalType       = trim(strtolower($item['type'] ?? ''));

            $isMatch =
                $goalKpi        === $excelKpi &&
                $goalPeriod     === $excelPeriod &&
                $goalCalcMethod === $excelCalcMethod &&
                $goalWeightage  === $excelWeightage &&
                $goalType       === $excelType;

            if (!$isMatch) {
                continue;
            }

            // Validasi review_period
            if (
                !array_key_exists('review_period', $item)
                || $item['review_period'] === null
                || $item['review_period'] === ''
            ) {
                $message = sprintf(
                    'Review Period not found. Employee ID: %s, KPI: %s',
                    $employeeId,
                    $kpiName
                );
                $this->transactions[] = [
                    'status'      => 'ERROR',
                    'employee_id' => $employeeId,
                    'kpi'         => $kpiName,
                    'message'     => $message,
                ];
                Log::error($message);
                throw new \Exception($message);
            }

            $kpiIndex    = $indexForm;
            $kpi         = $item;
            $reviewPeriod = $this->mapReviewPeriod($kpi['review_period']);
            break; // aman break karena kombinasi 5 field sudah unik
        }

        Log::debug('Matched KPI', ['kpiIndex' => $kpiIndex, 'kpi' => $kpi]);

        if ($kpiIndex === null || !$kpi) {
            Log::warning("KPI not found: {$kpiName} ({$employeeId})", [
                'excel_period'      => $excelPeriod,
                'excel_calc_method' => $excelCalcMethod,
                'excel_weightage'   => $excelWeightage,
                'excel_type'        => $excelType,
            ]);
            $this->transactions[] = [
                'status'      => 'ERROR',
                'employee_id' => $employeeId,
                'kpi'         => $kpiName,
                'message'     => "KPI '{$kpiName}' tidak ditemukan. Pastikan Weightage, Type, Review Period, dan Calculation Method sesuai dengan data goal.",
            ];
            continue;
        }

        $kpiId = $kpi['kpi_id'] ?? null;
        if (!$kpiId) {
            $kpiId = (string) Str::uuid();
            $formData[$kpiIndex]['kpi_id'] = $kpiId;
            $goal->form_data = json_encode($formData);
            $goal->save();
        }

        $monthColumns = [
            'jan' => 1,  'feb' => 2,  'mar' => 3,
            'apr' => 4,  'may' => 5,  'jun' => 6,
            'jul' => 7,  'aug' => 8,  'sep' => 9,
            'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        foreach ($monthColumns as $column => $month) {
            if ($month % $reviewPeriod !== 0) {
                continue;
            }

            $value = $row[$column] ?? null;
            $this->rows[] = [
                'goal_id'     => $goal->id,
                'employee_id' => $employeeId,
                'kpi_id'      => $kpiId,
                'kpi'         => $kpiName,
                'month'       => $month,
                'value'       => ($value === null || trim((string)$value) === '')
                    ? null
                    : $this->kpiService->normalizeExcelDecimal($value),
            ];
        }
    }
}

protected function normalizeCalcMethod(string $method): string
{
    return match (strtolower(trim($method))) {
        'sum', 'sum/total', 'total'  => 'sum',
        'average', 'avg'             => 'average',
        'min', 'minimum'             => 'min',
        'max', 'maximum'             => 'max',
        'last', 'last value'         => 'last',
        default                      => strtolower(trim($method)),
    };
}

    /**
     * Save imported data into database
     */
    public function saveToDatabase(): void
    {
        DB::beginTransaction();

        try {

            foreach ($this->rows as $row) {

                $goal = Goal::find($row['goal_id']);

                if (!$goal) {
                    continue;
                }

                $employeeId = $goal->employee_id;

                $approverId = $this->kpiService->layerApproval($employeeId);

                $status = $employeeId != Auth::user()->employee_id
                    ? 'Approved'
                    : 'Pending';

                $existing = KPIAchievement::where('goal_id', $row['goal_id'])
                    ->where('kpi_id', $row['kpi_id'])
                    ->where('month', $row['month'])
                    ->first();

                // delete jika kosong
                if (
                    ($row['value'] === null
                    || trim((string)$row['value']) === '')
                    && $existing
                ) {

                Log::debug('Delete jika kosong', ['processed_rows' => $row['value']]);
                    
                    KPIAchievementSnapshotService::insertOne(
                        $existing,
                        Auth::user()->employee_id,
                        Auth::id()
                        );
                        
                    $existing->delete();

                    continue;
                }

                if (
                    (
                        $row['value'] === null
                        || trim((string)$row['value']) === ''
                    )
                    && !$existing
                ) {

                    Log::debug(
                        'Skip jika kosong',
                        [
                            'value' => $row['value'],
                            'month' => $row['month'],
                            'kpi' => $row['kpi'],
                        ]
                    );

                    continue;
                }

                // skip jika bulan belum masuk
                if ($row['month'] > now()->month) {

                    $this->transactions[] = [
                        'status' => 'SUCCESS',
                        'employee_id' => $employeeId,
                        'kpi' => $row['kpi'] ?? null,
                        'message' => sprintf(
                            'Month %s skipped because period not reached',
                            $row['month']
                        ),
                    ];

                    continue;
                }

                if ($existing) {

                    KPIAchievementSnapshotService::insertOne(
                        $existing,
                        Auth::user()->employee_id,
                        Auth::id()
                    );

                    $existing->value = $row['value'];
                    $existing->approval_status = $status;
                    $existing->current_approver_employee_id = $approverId;

                    if($status == 'Pending'){
                        $existing->created_by = Auth::id();
                    }

                    if ($status === 'Approved') {
                        $existing->approval_date = now();
                    } else {
                        $existing->approval_date = null;
                    }

                    $existing->save();

                    $this->transactions[] = [
                        'status' => 'SUCCESS',
                        'employee_id' => $employeeId,
                        'kpi' => $row['kpi'] ?? null,
                    ];

                } else {

                    $achievement = new KPIAchievement();
                    $achievement->goal_id = $row['goal_id'];
                    $achievement->kpi_id = $row['kpi_id'];
                    $achievement->month = $row['month'];
                    $achievement->value = $row['value'];
                    $achievement->approval_status = $status;
                    $achievement->current_approver_employee_id = $approverId;
                    $achievement->created_by = Auth::id();

                    if ($status === 'Approved') {
                        $achievement->approval_date = now();
                    }

                    $achievement->save();

                    KPIAchievementSnapshotService::insertOne(
                        $achievement,
                        Auth::user()->employee_id,
                        Auth::id()
                    );

                    $this->transactions[] = [
                        'status' => 'SUCCESS',
                        'employee_id' => $employeeId,
                        'kpi' => $row['kpi'] ?? null,
                    ];
                }
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            $this->transactions[] = [
                'status' => 'ERROR',
                'employee_id' => $row['employee_id'] ?? null,
                'kpi' => $row['kpi'] ?? null,
                'message' => $e->getMessage(),
            ];

            // Log::error('Achievement import failed', [
            //     'message' => $e->getMessage(),
            //     'line' => $e->getLine(),
            //     'file' => $e->getFile(),
            // ]);

            throw $e;
        }
    }

    /**
     * Save import logs / transaction logs
     */
    public function saveTransaction(): void
    {
        $successEmployees = [];
        $errorEmployees = [];

        foreach ($this->transactions as $transaction) {

            $employeeId = $transaction['employee_id'] ?? null;

            if (!$employeeId) {
                continue;
            }

            if (($transaction['status'] ?? null) === 'SUCCESS') {

                $successEmployees[$employeeId] = true;

            } elseif (($transaction['status'] ?? null) === 'ERROR') {

                $errorEmployees[$employeeId] = true;
            }
        }

        $detailErrors = array_filter(
            $this->transactions,
            fn ($item) => ($item['status'] ?? null) === 'ERROR'
        );

        AchievementImportTransaction::create([
            'success' => count($successEmployees),

            'error' => count($errorEmployees),

            'detail_error' => !empty($detailErrors)
                ? json_encode(array_values($detailErrors), JSON_PRETTY_PRINT)
                : null,

            'file_uploads' => $this->filePath,

            'submit_by' => Auth::id(),
        ]);

        // Log::info('Achievement import transaction saved', [
        //     'success_employee_count' => count($successEmployees),
        //     'error_employee_count' => count($errorEmployees),
        // ]);
    }

    /**
     * Mapping review period
     */
    protected function mapReviewPeriod($period): int
    {
        return match (strtolower($period)) {
            '1', 'monthly' => 1,

            '2',
            'bi-monthly',
            'bimonthly',
            'bi monthly' => 2,

            '3', 'quarterly' => 3,

            '6', 'semester' => 6,

            '12',
            'annual' => 12,

            default => 1,
        };
    }

    public function validateImportPermission(): void
    {
        if ($this->type !== 'self') {
            return;
        }

        $successEmployeeIds =
            collect($this->rows)
            ->pluck('employee_id')
            ->unique()
            ->values();

        if ($successEmployeeIds->isEmpty()) {

            throw new \Exception(
                'No matching employee data found. Please use your own achievement template.'
            );
        }
    }

}