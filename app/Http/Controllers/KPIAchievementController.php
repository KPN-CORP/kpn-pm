<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KPIAchievement;
use App\Models\Goal;
use App\Models\KPIAchievementSnapshot;
use App\Services\AppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KPIAchievementController extends Controller
{
    protected $user;
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
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
                'kpi_index' => $request->kpi_index,
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
        }

        return view('pages.goals.update-achievement', compact(
            'parentLink',
            'link',
            'formData',
            'id',
            'selfUpdate'
        ));
    }

    public function bulkStore(Request $request)
    {
        // $request->submit_type bisa "draft" atau "submit" ////////////////////
        $status = $request->submit_type === 'submit' ? 'Submitted' : 'Draft';

        $isSubmit = $status === 'Submitted';
        
        $request->validate([
            'goal_id' => 'required|string',
            'ach' => 'nullable|array',
            'attachment' => 'array',
            'attachment.*.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048'
            ], [
            'attachment.*.*.max' => 'Ukuran file maksimal 2MB',
            'attachment.*.*.mimes' => 'File harus PDF/JPG/PNG'
            ]);
            
        $goal = Goal::findOrFail($request->goal_id);
        $employeeId = $goal->employee_id;
        $formData = json_decode($goal->form_data, true);

        // ensure semua KPI punya kpi_id
        foreach ($formData as $i => $row) {

            $kpiId = $request->kpi_id[$i] ?? null;

            if (!$kpiId) {
                // generate UUID baru
                $kpiId = (string) Str::uuid();

                // update ke formData
                $formData[$i]['kpi_id'] = $kpiId;

                // update ke request (biar dipakai di bawah)
                $kpiIds[$i] = $kpiId;
            }
        }

        // update goal jika ada perubahan kpi_id
        $goal->form_data = json_encode($formData);
        $goal->save();

        // proses achievement
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

                $filePath = $existing->file ?? null;

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

                $achievement = new KPIAchievement();
                $achievement->goal_id = $request->goal_id;
                $achievement->kpi_id = $kpiId;
                $achievement->month = $month;
                $achievement->value = $value;
                $achievement->file = $filePath;
                $achievement->save();

                if ($isSubmit) {
                    $achievementSnapshots = new KPIAchievementSnapshot();
                    $achievementSnapshots->goal_id = $request->goal_id;
                    $achievementSnapshots->kpi_id = $kpiId;
                    $achievementSnapshots->month = $month;
                    $achievementSnapshots->value = $value;
                    $achievementSnapshots->file = $filePath;
                    $achievementSnapshots->employee_id = $employeeId;
                    $achievementSnapshots->created_by = Auth::id();
                    $achievementSnapshots->save();
                }
            }
        }

        return $this->user != $request->employee_id 
                ? redirect('team-goals')->with('success', 'Achievements submitted successfully')
                : redirect('goals')->with('success', 'Avhievements submitted successfully');
    }

    function approvalAchievement($id)
    {
        $parentLink = __('Achievement');
        $link = __('Edit');
        $period = $this->appService->goalPeriod();
        $goal = Goal::findOrFail($id);
        $formData = json_decode($goal->form_data, true);

        return view('pages.goals.approval-achievement', compact(
            'formData',
            'id',
            'parentLink',
            'link'
        ));
    }
}