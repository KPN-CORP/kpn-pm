<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\KPIAchievement;
use App\Services\KPIService;
use Illuminate\Http\Request;

class KPIScoreController extends Controller
{
    public function calculate($goalId)
    {
        $goal = Goal::findOrFail($goalId);
        $formData = json_decode($goal->form_data, true);

        $results = [];
        $totalScore = 0;

        foreach ($formData as $index => $kpi) {

            $values = KPIAchievement::where('goal_id', $goalId)
                ->where('kpi_index', $index)
                ->orderBy('month')
                ->pluck('value')
                ->toArray();

            // aggregate
            $actual = KPIService::aggregate(
                $kpi['calculation_method'],
                $values
            );

            // achievement %
            $achievement = KPIService::achievement(
                $actual,
                (float)$kpi['target'],
                $kpi['type']
            );

            // normalize
            $normalized = KPIService::normalize($achievement);

            // final score
            $score = KPIService::finalScore(
                $normalized,
                (float)$kpi['weightage']
            );

            $totalScore += $score;

            $results[] = [
                'kpi' => $kpi['kpi'],
                'target' => $kpi['target'],
                'actual' => round($actual, 2),
                'achievement_percent' => round($achievement, 2),
                'normalized' => round($normalized, 2),
                'weight' => $kpi['weightage'],
                'score' => round($score, 2),
            ];
        }

        return response()->json([
            'goal_id' => $goalId,
            'total_score' => round($totalScore, 2),
            'kpis' => $results
        ]);
    }
}
