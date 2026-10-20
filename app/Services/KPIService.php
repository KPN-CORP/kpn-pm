<?php 
namespace App\Services;

use App\Models\ApprovalLayer;
use App\Models\Goal;
use App\Models\KPIAchievement;

class KPIService
{
    public static function aggregate(
        string $method,
        array $values,
        ?int $reviewPeriod = null
    ): float
    {
        if (empty($values)) {
            return 0;
        }

        return match ($method) {

            'average' => self::calculateAverage($values, $reviewPeriod),

            'sum' => array_sum($values),

            'last' => (float) end($values),

            'max' => max($values),

            'min' => min($values),

            default => 0,
        };
    }

    public static function achievement(float $actual, float $target, string $type)
    {
        if ($target == 0) return 0;

        return match ($type) {
            'Higher Better' => ($actual / $target) * 100,
            'Lower Better'  => ($target / max($actual, 1)) * 100,
            'Exact Value'   => $actual == $target ? 100 : 0,
            default         => 0,
        };
    }

    public static function normalize(float $score): float
    {
        return min(max($score, 0), 150); // cap 150%
    }

    public static function finalScore(float $normalized, float $weight)
    {
        return ($normalized * $weight) / 100;
    }

    public static function calculate(string $goalId)
    {
        $goal = Goal::findOrFail($goalId);
        $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        // 🔥 ambil semua achievement sekali
        $achievements = KPIAchievement::where('goal_id', $goalId)
            ->get()
            ->groupBy('kpi_id');

        $results = [];
        $totalScore = 0;

        foreach ($formData as $kpi) {

            $kpiId = $kpi['kpi_id'] ?? null;
            if (!$kpiId) continue;

            $values = collect($achievements[$kpiId] ?? [])
                ->sortBy('month')
                ->pluck('value')
                ->toArray();

            $actual = self::aggregate(
                $kpi['calculation_method'] ?? 'last',
                $values, $kpi['review_period'] ?? null
            );

            $achievement = self::achievement(
                $actual,
                (float)$kpi['target'],
                $kpi['type']
            );

            $normalized = self::normalize($achievement);

            $score = self::finalScore(
                $normalized,
                (float)$kpi['weightage']
            );

            $totalScore += $score;

            $results[] = [
                'kpi_id' => $kpiId,
                'kpi' => $kpi['kpi'],
                'target' => $kpi['target'],
                'actual' => round($actual, 2),
                'achievement' => round($achievement, 2),
                'normalized' => round($normalized, 2),
                'weight' => $kpi['weightage'],
                'score' => round($score, 2),
            ];
        }

        return [
            'goal_id' => $goalId,
            'total_score' => round($totalScore, 2),
            'kpis' => $results
        ];
    }

    /**
     * Actual per KPI (index sesuai urutan form_data goal), dihitung dari goal achievement
     * dengan logic yang sama seperti halaman My Goal. KPI tanpa achievement = 0.
     */
    public static function goalActuals(Goal $goal): array
    {
        $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        $achievements = KPIAchievement::where('goal_id', $goal->id)
            ->get()
            ->groupBy('kpi_id');

        $actuals = [];

        foreach ($formData ?? [] as $index => $kpi) {
            $kpiId = $kpi['kpi_id'] ?? null;

            $values = collect($kpiId ? ($achievements[$kpiId] ?? []) : [])
                ->sortBy('month')
                ->pluck('value')
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->values()
                ->toArray();

            $actuals[$index] = empty($values)
                ? 0
                : round(self::aggregate($kpi['calculation_method'] ?? 'last', $values, $kpi['review_period'] ?? null), 2);
        }

        return $actuals;
    }

    /**
     * Isi $goalData[i]['actual'] dari goal achievement (untuk input Achievement di form appraisal).
     */
    public static function withGoalActuals(array $goalData, Goal $goal): array
    {
        $actuals = self::goalActuals($goal);

        foreach ($goalData as $index => &$kpi) {
            $kpi['actual'] = $actuals[$index] ?? 0;
        }

        return $goalData;
    }

    /**
     * Timpa nilai achievement form KPI yang dikirim dari request dengan nilai dari goal achievement.
     */
    public static function applyGoalActuals(array $formData, Goal $goal): array
    {
        $actuals = self::goalActuals($goal);

        foreach ($formData as &$form) {
            if (($form['formName'] ?? null) !== 'KPI') {
                continue;
            }

            foreach ($actuals as $index => $actual) {
                $form[$index]['achievement'] = (string) $actual;
            }
        }

        return $formData;
    }

    function layerApproval(string $employeeId)
    {
        $approvalLayer = ApprovalLayer::query()
            ->where('employee_id', $employeeId)
            ->orderBy('layer')
            ->first();

        if (!$approvalLayer) {
            return null; // Atau lempar exception jika perlu
        }

        return $approvalLayer->approver_id;
    }

    public function normalizeDecimal(float|string|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private static function calculateAverage(array $values, int $reviewPeriod): float
    {
        $total = array_sum($values);

        $divisor = match ((int) $reviewPeriod) {

            1 => 12, // Monthly

            2 => 6,  // Bi-Monthly

            3 => 4,  // Quarterly

            6 => 2,  // Semester

           12 => 1,  // Annual

            default => count($values),
        };

        if ($divisor <= 0) {
            return 0;
        }

        return $total / $divisor;
    }

    function normalizeExcelDecimal($value): ?float
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $value = trim((string)$value);

        // remove space
        $value = str_replace(' ', '', $value);

        // negative
        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = ltrim($value, '-');
        }

        $hasDot = str_contains($value, '.');
        $hasComma = str_contains($value, ',');

        // 1.000.000 / 1,000,000
        if ($hasDot && !$hasComma) {

            $parts = explode('.', $value);

            if (
                count($parts) > 1 &&
                collect(array_slice($parts, 1))
                    ->every(fn($p) => strlen($p) === 3)
            ) {
                $value = str_replace('.', '', $value);

            } else {
                $value = str_replace(',', '', $value);
            }

        } elseif ($hasComma && !$hasDot) {

            $parts = explode(',', $value);

            // 1,000,000
            if (
                count($parts) > 1 &&
                collect(array_slice($parts, 1))
                    ->every(fn($p) => strlen($p) === 3)
            ) {
                $value = str_replace(',', '', $value);

            } else {
                // decimal EU
                $value = str_replace(',', '.', $value);
            }

        } else {

            // 1.000,50
            if ($hasDot && $hasComma) {

                $lastDot = strrpos($value, '.');
                $lastComma = strrpos($value, ',');

                if ($lastComma > $lastDot) {

                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);

                } else {

                    $value = str_replace(',', '', $value);
                }
            }
        }

        $value = (float)$value;

        return $negative ? -$value : $value;
    }
}