<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KPIAchievement;
use App\Models\Goal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KPIAchievementController extends Controller
{
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

    public function index($goalId)
    {
        $goal = Goal::findOrFail($goalId);
        $formData = json_decode($goal->form_data, true);

        $achievements = KPIAchievement::where('goal_id', $goalId)
            ->orderBy('kpi_index')
            ->orderBy('month')
            ->get()
            ->groupBy('kpi_index');

        $result = [];

        foreach ($formData as $index => $kpi) {

            $data = $achievements[$index] ?? collect();

            $result[] = [
                'kpi_index' => $index,
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

        $goal = Goal::findOrFail($id);

        // KPI dari goal
        $formData = json_decode($goal->form_data, true);

        $achievements =KPIAchievement::where('goal_id', $id)
            ->get()
            ->groupBy('kpi_index');

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

            $formData[$i]['review_period_label'] = $mapLabel($reviewPeriodOption, $row['review_period'] ?? null);
            $formData[$i]['calculation_method_label'] = $mapLabel($calculationMethodOption, $row['calculation_method'] ?? null);

            for ($m = 1; $m <= 12; $m++) {
                $formData[$i]['ach'][$m] = null;
            }

            if (isset($achievements[$i])) {
                foreach ($achievements[$i] as $ach) {
                    $month = (int)$ach->month; // 1-12
                    $formData[$i]['ach'][$month] = $ach->value;
                    $formData[$i]['attachment'][$month] = $ach->file ?? null;
                }
            }
        }

        return view('pages.goals.update-achievement', compact(
            'parentLink',
            'link',
            'formData',
            'id'
        ));
    }

    public function bulkStore(Request $request)
    {
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
        $formData = json_decode($goal->form_data, true);

        foreach ($request->ach as $kpiIndex => $months) {

            if (!isset($formData[$kpiIndex])) continue;

            $kpi = $formData[$kpiIndex];
            $period = (int) $kpi['review_period'];

            foreach ($months as $month => $value) {

                $month = (int)$month;

                if ($month % $period !== 0) continue;

                $existing = KPIAchievement::where('goal_id', $request->goal_id)
                    ->where('kpi_index', $kpiIndex)
                    ->where('month', $month)
                    ->first();

                $filePath = $existing->file ?? null;

                if ($request->hasFile("attachment.$kpiIndex.$month")) {

                    if ($existing || $value === null || $value === '') {
                        if ($existing->file) {
                            Storage::disk('public')->delete($existing->file);
                        }
                        $existing->delete(); // Soft delete the existing achievement
                    }
                    $file = $request->file("attachment.$kpiIndex.$month");

                    $filePath = $file->store(
                        "kpi-achievements/{$request->goal_id}/{$kpiIndex}",
                        'public'
                    );
                }

                $achievement = new KPIAchievement();
                $achievement->goal_id = $request->goal_id;
                $achievement->kpi_index = $kpiIndex;
                $achievement->month = $month;
                $achievement->value = $value;
                $achievement->file = $filePath;
                $achievement->save();
            }
        }

        return redirect()->back()->with('success', 'Achievement saved');
    }
}