<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KPIAchievement;
use App\Models\Goal;
use App\Models\KPIAchievementSnapshot;
use App\Services\AppService;
use App\Services\KPIAchievementService;
use App\Services\KPIService;
use App\Services\KPIAchievementSnapshotService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KPIAchievementController extends Controller
{
    protected $user;
    protected $appService;
    protected $kpiService;

    public function __construct(AppService $appService, KPIService $kpiService)
    {
        $this->appService = $appService;
        $this->kpiService = $kpiService;
        $this->user = Auth::user()->employee_id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'goal_id'   => 'required|string',
            'kpi_index' => 'required|integer|min:0',
            'month'     => 'required|integer|min:1|max:12',
            'value'     => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg,xlsx,csv,pptx,xls|max:2048'
        ]);

        $goal = Goal::findOrFail($request->goal_id);
        $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        if (!isset($formData[$request->kpi_index])) {
            return response()->json(['message' => 'KPI tidak ditemukan'], 422);
        }

        $kpi = $formData[$request->kpi_index];
        $period = (int) $kpi['review_period'];

        if ($request->month % $period !== 0) {
            return response()->json([
                'message' => "Bulan tidak valid untuk period"
            ], 422);
        }

        $filePath = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            $filePath = $file->store(
                "kpi-achievements/{$request->goal_id}/{$request->kpi_index}",
                'public'
            );
        }

        $achievement = KPIAchievement::updateOrCreate(
            [
                'goal_id'   => $request->goal_id,
                'kpi_id' => $request->kpi_index,
                'month'     => $request->month,
            ],
            [
                'value' => $request->value,
                'file' => $filePath
            ]
        );

        return response()->json([
            'message' => 'Saved',
            'data' => $achievement
        ]);
    }

    public function index($id)
    {
        $goal = Goal::findOrFail($id);
        $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        $period = $this->appService->goalPeriod();

        if ($goal->form_status != "Approved") {
            Session::flash('error', [
                'title' => 'Cannot update Achievements',
                'message' => "Your Goals for $period are not fully Approved."
            ]);
            return redirect('goals');
        }

        $achievements = KPIAchievement::where('goal_id', $id)
            ->orderBy('id')
            ->orderBy('month')
            ->get()
            ->groupBy('kpi_id');

        $result = [];

        foreach ($formData as $index => $kpi) {

            $data = $achievements[$index] ?? collect();

            $result[] = [
                'kpi_id' => $kpi['kpi_id'],
                'kpi_name'  => $kpi['kpi'],
                'review_period' => $kpi['review_period'],
                'calculation_method' => $kpi['calculation_method'],
                'data' => $data->values()
            ];
        }

        return response()->json($result);
    }

    public function editAchievement($id)
    {
        $parentLink = __('Achievement');
        $link = __('Edit');
        $period = $this->appService->goalPeriod();

        $goal = Goal::findOrFail($id);

        if ($goal->form_status != "Approved") {
            if ($goal->employee_id != $this->user) {
                Session::flash('error', [
                    'title' => 'Cannot update Achievements',
                    'message' => "The Employee Goals for $period are not fully Approved."
                ]);

                return redirect('team-goals');
            }
            Session::flash('error', [
                'title' => 'Cannot update Achievements',
                'message' => "Your Goals for $period are not fully Approved."
            ]);

            return redirect('goals');
        }

        $selfUpdate = $goal->employee_id == $this->user;

        // KPI dari goal
        $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        $achievements = KPIAchievement::where('goal_id', $id)
            ->get()
            ->groupBy('kpi_id');

        $approvalInfo = KPIAchievement::where('goal_id', $id)
            ->first();

            // load options
        $options = json_decode(File::get(base_path('resources/goal.json')), true);

        $reviewPeriodOption = $options['Review Period'] ?? [];
        $calculationMethodOption = $options['Calculation Method'] ?? [];

        // helper mapping
        $mapLabel = function ($options, $value) {
            foreach ($options as $group) {
                foreach ($group as $opt) {
                    if ($opt['value'] == $value) return $opt['label'];
                }
            }
            return '-';
        };

        $achievementData = KPIAchievementService::getByGoal($goal->id) ?? [];
        $isEmptyAchievement = empty($achievementData);

        foreach ($formData as $i => $row) {

            $kpiId = $row['kpi_id'] ?? null;

            $formData[$i]['kpi_id'] = $kpiId;

            // label mapping
            $formData[$i]['review_period_label'] = $mapLabel($reviewPeriodOption, $row['review_period'] ?? null);
            $formData[$i]['calculation_method_label'] = $mapLabel($calculationMethodOption, $row['calculation_method'] ?? null);

            // init month 1-12
            for ($m = 1; $m <= 12; $m++) {
                $formData[$i]['ach'][$m] = null;
                $formData[$i]['attachment'][$m] = null;
            }

            if ($kpiId && isset($achievements[$kpiId])) {

                foreach ($achievements[$kpiId] as $ach) {
                    $month = (int)$ach->month;

                    $formData[$i]['ach'][$month] = $ach->value;
                    $formData[$i]['attachment'][$month] = $ach->file ?? null;

                }
            }

            $values = collect($formData[$i]['ach'])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->values()
                ->toArray();

            $actual = $this->kpiService->aggregate(
                $formData[$i]['calculation_method'] ?? 'last',
                $values, $row['review_period'] ?? null
            );

            $achievement = $isEmptyAchievement
            ? 0
            : $this->kpiService->achievement(
                $actual,
                (float)($formData[$i]['target'] ?? 0),
                $formData[$i]['type'] ?? 'Higher Better'
            );

            $formData[$i]['actual'] = empty($values) ? '-' : round($actual, 2);
            $formData[$i]['achievement'] = empty($values) ? 0 : round($achievement, 2);
        }

        return view('pages.goals.update-achievement', compact(
            'parentLink',
            'link',
            'formData',
            'goal',
            'id',
            'selfUpdate',
            'approvalInfo'
        ));
    }

    public function bulkStore(Request $request)
    {
        try {
            DB::beginTransaction();

            // ================= VALIDATION =================
            $request->validate([
                'goal_id' => 'required|string',
                'ach' => 'nullable|array',
                'attachment' => 'array',
                'attachment.*.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg,xlsx,csv,pptx,xls|max:2048'
            ], [
                'attachment.*.*.max' => 'Ukuran file maksimal 2MB',
                'attachment.*.*.mimes' => 'File harus PDF/JPG/PNG/XLSX/XLS/CSV/PPTX'
            ]);

            $timeNow = now();

            // ================= GET GOAL =================
            $goal = Goal::findOrFail($request->goal_id);
            $employeeId = $goal->employee_id;
            $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

            // ================= STATUS =================
            if ($request->submit_type === 'draft') {
                $status = 'Draft';
            } else {
                $status = $employeeId != $this->user ? 'Approved' : 'Pending';
            }

            $isSubmit = $status != 'Draft';

            $approverId = $this->kpiService->layerApproval($employeeId);

            // ================= ENSURE KPI ID =================
            $kpiIds = [];

            foreach ($formData as $i => $row) {
                $kpiId = $request->kpi_id[$i] ?? null;

                if (!$kpiId) {
                    $kpiId = (string) Str::uuid();
                    $formData[$i]['kpi_id'] = $kpiId;
                }

                $kpiIds[$i] = $kpiId;
            }

            // update goal
            $goal->form_data = json_encode($formData);
            $goal->save();

            // ================= PROCESS ACHIEVEMENT =================
            foreach ($request->ach as $kpiIndex => $months) {

                if (!isset($formData[$kpiIndex])) continue;

                $kpi = $formData[$kpiIndex];
                $period = (int) $kpi['review_period'];

                $kpiId = $request->kpi_id[$kpiIndex] ?? $kpiIds[$kpiIndex];
                if (!$kpiId) continue;

                foreach ($months as $month => $value) {

                    $month = (int)$month;

                    if ($month % $period !== 0) continue;

                    $existing = KPIAchievement::where('goal_id', $request->goal_id)
                        ->where('kpi_id', $kpiId)
                        ->where('month', $month)
                        ->first();

                    $filePath = $existing ? ($existing->file ?? null) : null;

                    // FILE HANDLING
                    if ($request->hasFile("attachment.$kpiIndex.$month")) {

                        if ($existing) {
                            if ($existing->file) {
                                Storage::disk('public')->delete($existing->file);
                            }
                            // $existing->delete();
                        }

                        $file = $request->file("attachment.$kpiIndex.$month");

                        $filePath = $file->store(
                            "kpi-achievements/{$request->goal_id}/{$kpiIndex}",
                            'public'
                        );
                    }

                    // SKIP jika kosong semua
                    // DELETE jika value dikosongkan dan tidak ada attachment
                    if (
                        ($value === null || trim((string)$value) === '')
                        && !$request->hasFile("attachment.$kpiIndex.$month")
                        && $existing
                    ) {

                        if ($isSubmit) {
                            KPIAchievementSnapshotService::insertOne(
                                $existing,
                                $this->user,
                                Auth::id()
                            );
                        }

                        // hapus file lama jika ada
                        if ($existing->file) {
                            Storage::disk('public')
                                ->delete($existing->file);
                        }

                        $existing->delete();

                        continue;
                    }

                    // skip jika benar-benar kosong dan tidak ada existing
                    if (
                        ($value === null || trim((string)$value) === '')
                        && !$filePath
                    ) {
                        continue;
                    }

                    $normalizedValue = (
                        $value === null ||
                        trim((string)$value) === ''
                    )
                        ? null
                        : $this->kpiService->normalizeDecimal($value);

                    if ($existing) {
                        if ($isSubmit) {
                            KPIAchievementSnapshotService::insertOne($existing, $this->user, Auth::id());
                        }

                        $existing->value = $normalizedValue;
                        $existing->file = $filePath;
                        $existing->current_approver_employee_id = $approverId ?? null;
                        $existing->approval_status = $status;

                        if ($status == "Approved") {
                            $existing->approval_date = $timeNow;
                        } else if ($status == "Pending") {
                            $existing->created_by = Auth::id();
                            $existing->approval_date = null;
                        }

                        $existing->save();
                    } else {
                        $achievement = new KPIAchievement();
                        $achievement->goal_id = $request->goal_id;
                        $achievement->kpi_id = $kpiId;
                        $achievement->month = $month;
                        $achievement->value = $normalizedValue;
                        $achievement->file = $filePath;
                        $achievement->current_approver_employee_id = $approverId ?? null;
                        $achievement->approval_status = $status;
                        $achievement->created_by = Auth::id();

                        $achievement->save();

                        if ($isSubmit) {
                            KPIAchievementSnapshotService::insertOne($achievement, $this->user, Auth::id());
                        }
                    }
                }
            }

            DB::commit();

            return $this->user != $request->employee_id
                ? redirect('team-goals')->with('success', 'Achievements submitted successfully')
                : redirect('goals')->with('success', 'Achievements submitted successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            
            // TAMPILKAN ERROR KE LAYAR UNTUK DEBUGGING
            // dd([
            //     'Error Message' => $e->getMessage(),
            //     'File' => $e->getFile(),
            //     'Line' => $e->getLine()
            // ]);
        
            Log::error('Bulk KPI Achievement Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        
            return back()->with('error', 'Achievements submitted failed!');
        }
    }

    public function approvalAchievement($id)
    {
        try {
            $goal = Goal::with('employee.managerL1')->findOrFail($id);

            $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true) ?? [];

            $options = json_decode(File::get(base_path('resources/goal.json')), true);

        $reviewPeriodOption = $options['Review Period'] ?? [];
        $calculationMethodOption = $options['Calculation Method'] ?? [];

        // helper mapping
        $mapLabel = function ($options, $value) {
            foreach ($options as $group) {
                foreach ($group as $opt) {
                    if ($opt['value'] == $value) return $opt['label'];
                }
            }
            return '-';
        };

        $achievementData = KPIAchievementService::getByGoal($goal->id) ?? [];

        $approvalInfo = KPIAchievement::where('goal_id', $goal->id)
            ->value('approval_info');

        $isEmptyAchievement = empty($achievementData);

        $isManager = KPIAchievement::where('goal_id', $goal->id)
                ->where('approval_status', 'Pending')
                ->where('current_approver_employee_id', $this->user)
                ->exists();

            // ✅ CURRENT
            $achievements = KPIAchievement::where('goal_id', $id)
                ->orderBy('month')
                ->get()
                ->groupBy('kpi_id');

            // ✅ SNAPSHOT
            $snapshots = KPIAchievementSnapshot::where('goal_id', $id)
                ->where('approval_status', 'Approved')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('kpi_id');
                // dd($snapshots);

            $months = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            foreach ($formData as $i => $row) {

                $kpiId = $row['kpi_id'] ?? null;

                // INIT AFTER
                $formData[$i]['months'] = [];

                $formData[$i]['review_period_label'] = $mapLabel($reviewPeriodOption, $row['review_period'] ?? null);
                $formData[$i]['calculation_method_label'] = $mapLabel($calculationMethodOption, $row['calculation_method'] ?? null);

                foreach ($months as $num => $label) {
                    $formData[$i]['months'][$num] = [
                        'label' => $label,
                        'value' => null,
                        'file'  => null,
                    ];
                }

                // AFTER
                if ($kpiId && $achievements->has($kpiId)) {
                    foreach ($achievements[$kpiId] as $ach) {
                        $m = (int) $ach->month;

                        $formData[$i]['months'][$m]['value'] = $ach->value;
                        $formData[$i]['months'][$m]['file']  = $ach->file;
                    }
                }

                // INIT BEFORE
                $formData[$i]['old_months'] = [];

                foreach ($months as $num => $label) {
                    $formData[$i]['old_months'][$num] = [
                        'label' => $label,
                        'value' => null,
                        'file'  => null,
                    ];
                }

                // BEFORE
                if ($kpiId && $snapshots->has($kpiId)) {

                    $latestPerMonth = $snapshots[$kpiId]
                        ->groupBy('month')
                        ->map(fn($items) => $items->sortByDesc('created_at')->first());

                    foreach ($latestPerMonth as $month => $snap) {
                        $m = (int) $month;

                        $formData[$i]['old_months'][$m]['value'] = $snap->value;
                        $formData[$i]['old_months'][$m]['file']  = $snap->file;
                    }
                }

                // FLAG
                $formData[$i]['has_old_data'] = collect($formData[$i]['old_months'])
                    ->pluck('value')
                    ->filter()
                    ->isNotEmpty();

                // init month 1-12
                for ($m = 1; $m <= 12; $m++) {
                    $formData[$i]['ach'][$m] = null;
                    $formData[$i]['attachment'][$m] = null;
                }

                if ($kpiId && isset($achievements[$kpiId])) {

                    foreach ($achievements[$kpiId] as $ach) {
                        $month = (int)$ach->month;

                        $formData[$i]['ach'][$month] = $ach->value;
                        $formData[$i]['attachment'][$month] = $ach->file ?? null;

                    }
                }

                $values = collect($formData[$i]['ach'])
                    ->filter(fn($v) => $v !== null && $v !== '')
                    ->values()
                    ->toArray();

                $actual = $this->kpiService->aggregate(
                    $formData[$i]['calculation_method'] ?? 'last',
                    $values, $row['review_period'] ?? null
                );

                $achievement = $isEmptyAchievement
                ? 0
                : $this->kpiService->achievement(
                    $actual,
                    (float)($formData[$i]['target'] ?? 0),
                    $formData[$i]['type'] ?? 'Higher Better'
                );

                $formData[$i]['actual'] = empty($values) ? '-' : round($actual, 2);
                $formData[$i]['achievement'] = empty($values) ? 0 : round($achievement, 2);
            }

            $employee = $goal->employee;
            $parentLink = $isManager ? __('Achievement') : __('On Behalf');
            $link = __('Approval');

            return view('pages.goals.approval-achievement', [
                'kpis' => $formData,
                'employee' => $employee,
                'approvalInfo' => $approvalInfo,
                'id' => $id,
                'parentLink' => $parentLink,
                'link' => $link,
                'isManager' => $isManager
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function approvalAchievementApprove(Request $request)
    {
        try {

            DB::beginTransaction();

            $request->validate([
                'goal_id' => 'required|string',
                'ach' => 'nullable|array'
            ]);

            $timeNow = now();

            $goalID = $request->goal_id;

            $newValues = $request->ach ?? [];

            $sendbackMessage = $request->sendback_message;

            // ================= APPROVER VALIDATION =================

            $hasInvalidApprover = KPIAchievement::where('goal_id', $goalID)
                ->where('approval_status', 'Pending')
                ->where(
                    'current_approver_employee_id',
                    '!=',
                    $this->user
                )
                ->exists();

            // ================= GET DATA =================

            $kpiAchievements = KPIAchievement::where(
                    "goal_id",
                    $goalID
                )
                ->where(
                    "approval_status",
                    "Pending"
                )
                ->whereNull("deleted_at")
                ->get();

            foreach ($kpiAchievements as $val) {

                KPIAchievementSnapshotService::insertOne(
                    $val,
                    $this->user,
                    Auth::id()
                );

                // ================= UPDATE / DELETE VALUE =================

                if (
                    isset($newValues[$val->kpi_id]) &&
                    array_key_exists(
                        $val->month,
                        $newValues[$val->kpi_id]
                    )
                ) {

                    $rawValue =
                        $newValues[
                            $val->kpi_id
                        ][
                            $val->month
                        ];

                    // DELETE jika dikosongkan
                    if (
                        $rawValue === null ||
                        trim((string)$rawValue) === ''
                    ) {

                        if ($val->file) {
                            Storage::disk('public')
                                ->delete($val->file);
                        }

                        $val->delete();

                        continue;
                    }

                    Log::debug('Normalize Decimal Raw Value', [
                        'rawValue' => $rawValue,
                        'type' => gettype($rawValue),
                        'is_numeric' => is_numeric($rawValue),
                        'achievement_id' => $val->id ?? null,
                    ]);

                    $normalizedValue = trim((string) $rawValue);

                    if (str_contains($normalizedValue, ',')) {
                        $normalizedValue = str_replace('.', '', $normalizedValue);
                        $normalizedValue = str_replace(',', '.', $normalizedValue);
                    } else {

                        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $normalizedValue)) {
                            $normalizedValue = str_replace('.', '', $normalizedValue);
                        }
                    }

                    $val->value = $this->kpiService->normalizeDecimal((float) $normalizedValue);

                }

                // ================= APPROVAL INFO =================

               $val->approval_info = $sendbackMessage
                    ?: $request->messages;

                $val->approval_status = $sendbackMessage
                    ? 'Draft'
                    : 'Approved';

                $val->approval_date =
                    $timeNow;

                $val->save();
            }

            DB::commit();

            if ($hasInvalidApprover) {

                return redirect()
                    ->route('onbehalf')
                    ->with(
                        'success',
                        'Achievements approved successfully'
                    );
            }

            return redirect('team-goals')
                ->with(
                    'success',
                    'Achievements approved successfully'
                );

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Approval Achievement Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }
}
