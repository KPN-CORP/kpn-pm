<?php

namespace App\Services;

use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AchievementReminderService
{
    public function getPendingGoals()
    {
        $goals = Goal::query()
            ->select([
                'id',
                'employee_id',
                'form_data',
                'form_status',
            ])
            ->with([
                'employee',
                'achievementList',
            ])
            ->where('form_status', 'Approved')
            ->where('period', now()->year)
            ->whereNotNull('form_data')
            ->get();

        Log::debug('Pending achievements found', [
            'total_goals' => $goals->count(),
            'total_achievements' => $goals
                ->flatMap(fn ($goal) => $goal->achievementList)
                ->count(),
        ]);

        return $goals->map(function ($goal) {

            $goal->reminderAchievements = collect();

            /*
            |--------------------------------------------------------------------------
            | Safety check form_data
            |--------------------------------------------------------------------------
            */

            Log::debug('Form Data Debug', [
                'goal_id' => $goal->id,
                'type' => gettype($goal->form_data),
                'value' => substr((string) json_encode($goal->form_data), 0, 200),
            ]);

            $formData = $this->parseFormData(
                $goal->form_data
            );

            Log::debug('Form Data Parsed', [
                'goal_id' => $goal->id,
                'type' => gettype($formData),
                'count' => count($formData),
            ]);

            if (empty($formData)) {
                return $goal;
            }

            foreach ($formData as $kpi) {

                /*
                |--------------------------------------------------------------------------
                | Validate review_period
                |--------------------------------------------------------------------------
                */

                if (
                    !is_array($kpi) ||
                    !array_key_exists('review_period', $kpi) ||
                    empty($kpi['review_period'])
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate kpi_id
                |--------------------------------------------------------------------------
                */

                if (
                    !array_key_exists('kpi_id', $kpi) ||
                    empty($kpi['kpi_id'])
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Find achievement by KPI
                |--------------------------------------------------------------------------
                */

                $achievements = $goal->achievementList
                    ->where('kpi_id', $kpi['kpi_id']);

                /*
                |--------------------------------------------------------------------------
                | Skip if KPI never has achievement
                |--------------------------------------------------------------------------
                */

                if ($achievements->isEmpty()) {

                    Log::debug('Achievement never updated', [
                        'goal_id' => $goal->id,
                        'employee_id' => $goal->employee_id,
                        'kpi_id' => $kpi['kpi_id'],
                        'kpi' => $kpi['kpi'],
                    ]);

                    $goal->reminderAchievements->push([
                        'achievement_id' => null,
                        'kpi_id' => $kpi['kpi_id'],
                        'kpi' => $kpi['kpi'],
                        'target' => $kpi['target'] ?? null,
                        'review_period' => $kpi['review_period'],
                        'updated_at' => null,
                        'status' => 'Never Updated',
                    ]);

                    continue;
                }

                $latestAchievement = $achievements
                    ->sortByDesc('updated_at')
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Check reminder
                |--------------------------------------------------------------------------
                */
                Log::debug('Checking pending achievement', [
                    'goal_id' => $goal->id,
                    'employee_id' => $goal->employee_id,
                    'kpi_id' => $kpi['kpi_id'],
                    'kpi' => $kpi['kpi'],
                    'review_period' => $kpi['review_period'],
                    'achievement_updated_at' => $latestAchievement?->updated_at,
                ]);

                if (
                    $this->shouldSendReminder(
                        (int) $kpi['review_period']
                    )
                ) {

                    $goal->reminderAchievements->push([
                        'achievement_id' => $latestAchievement->id,
                        'kpi_id' => $kpi['kpi_id'],
                        'kpi' => $kpi['kpi'],
                        'target' => $kpi['target'] ?? null,
                        'review_period' => $kpi['review_period'],
                        'updated_at' => $latestAchievement->updated_at,
                        'status' => 'Update Required',
                    ]);
                }
            }


            Log::debug('Pending KPI reminder generated', [
                'goal_id' => $goal->id,
                'employee_id' => $goal->employee_id,
                'pending_kpi_count' => $goal->reminderAchievements->count(),
            ]);

            return $goal;

        })->filter(function ($goal) {

            return $goal->reminderAchievements->isNotEmpty();

        })->values();
    }

    protected function shouldSendReminder(
        int $reviewPeriod
    ): bool {

        $result = now()->month % $reviewPeriod === 0;

        Log::debug('Reminder Check', [
            'review_period' => $reviewPeriod,
            'current_month' => now()->month,
            'result' => $result,
        ]);

        return $result;
    }

    private function parseFormData($formData): array
    {
        if (is_array($formData)) {
            return $formData;
        }

        if (empty($formData)) {
            return [];
        }

        $decoded = json_decode($formData, true);

        return json_last_error() === JSON_ERROR_NONE
            ? $decoded
            : [];
    }
}