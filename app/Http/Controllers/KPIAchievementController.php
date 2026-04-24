<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KPIAchievement;
use App\Models\Goal;
use App\Models\KPIAchievementSnapshot;
use App\Services\AppService;
use App\Services\KPIAchievementService;
use App\Services\KpiService;
use App\Services\KPIAchievementSnapshotService;
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

    public function __construct(AppService $appService, KpiService $kpiService)
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
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048'
        ]);

        $goal = Goal::findOrFail($request->goal_id);
        $formData = json_decode($goal->form_data, true);

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
        $formData = json_decode($goal->form_data, true);

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
        $formData = json_decode($goal->form_data, true);

        // ðŸ”¥ group by kpi_id (SUDAH BENAR)
        $achievements = KPIAchievement::where('goal_id', $id)
            ->get()
            ->groupBy('kpi_id');

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

            // ðŸ”¥ WAJIB: pastikan ada kpi_id
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

            // ðŸ”¥ FIX: pakai kpi_id, bukan index
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
                $values
            );

            $achievement = $isEmptyAchievement
            ? 0
            : $this->kpiService->achievement(
                $actual,
                (float)($formData[$i]['target'] ?? 0),
                $formData[$i]['type'] ?? 'Higher Better'
            );

            $formData[$i]['actual'] = round($actual, 2);
            $formData[$i]['achievement'] = round($achievement, 2);
        }

        return view('pages.goals.update-achievement', compact(
            'parentLink',
            'link',
            'formData',
            'goal',
            'id',
            'selfUpdate'
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
                'attachment.*.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048'
            ], [
                'attachment.*.*.max' => 'Ukuran file maksimal 2MB',
                'attachment.*.*.mimes' => 'File harus PDF/JPG/PNG'
            ]);

            // ================= GET GOAL =================
            $goal = Goal::findOrFail($request->goal_id);
            $employeeId = $goal->employee_id;
            $formData = json_decode($goal->form_data, true);

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
                            $existing->delete();
                        }

                        $file = $request->file("attachment.$kpiIndex.$month");

                        $filePath = $file->store(
                            "kpi-achievements/{$request->goal_id}/{$kpiIndex}",
                            'public'
                        );
                    }

                    // SKIP jika kosong semua
                    if (($value === null || $value === '') && !$filePath) {
                        continue;
                    }

                    $achievement = null;

                    if ($existing) {
                        $existing->value = $value;
                        $existing->file = $filePath;
                        $existing->current_approver_employee_id = $approverId ?? null;
                        $existing->approval_status = $status;

                        $existing->save();

                        $achievement = $existing;
                    } else {
                        $achievement = new KPIAchievement();
                        $achievement->goal_id = $request->goal_id;
                        $achievement->kpi_id = $kpiId;
                        $achievement->month = $month;
                        $achievement->value = $value;
                        $achievement->file = $filePath;
                        $achievement->current_approver_employee_id = $approverId ?? null;
                        $achievement->approval_status = $status;
                        $achievement->save();
                    }

                    if ($isSubmit) {
                        KPIAchievementSnapshotService::insertOne($achievement, $this->user, Auth::id());
                    }
                }
            }

            DB::commit();

            return $this->user != $request->employee_id
                ? redirect('team-goals')->with('success', 'Achievements submitted successfully')
                : redirect('goals')->with('success', 'Achievements submitted successfully');

        } catch (\Throwable $e) {

            DB::rollBack();

            // log error (penting untuk debugging production)
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
            $parentLink = __('Achievement');
            $link = __('Approval');

            $goal = Goal::with('employee')->findOrFail($id);

            $formData = json_decode($goal->form_data, true) ?? [];

            // ✅ CURRENT
            $achievements = KPIAchievement::where('goal_id', $id)
                ->orderBy('month')
                ->get()
                ->groupBy('kpi_id');

            // ✅ SNAPSHOT
            $snapshots = KPIAchievementSnapshot::where('goal_id', $id)
                ->where('employee_id', $this->user)
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
            }
            // 🔥 LANGSUNG PAKAI formData (TIDAK PERLU $kpis LAGI)

            $employee = $goal->employee;

            return view('pages.goals.approval-achievement', [
                'kpis' => $formData,
                'employee' => $employee,
                'id' => $id,
                'parentLink' => $parentLink,
                'link' => $link
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
