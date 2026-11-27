<?php 
namespace App\Services;

use App\Models\ApprovalLayer;
use App\Models\Goal;
use App\Models\KPIAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

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
     * Achievement tracking per bulan per KPI (index sesuai urutan form_data goal): [index => [1..12 => value|null]].
     *
     * Default dari goal achievement. Kalau $appraisalForms (list formData appraisal, dari DB atau request)
     * punya KPI dengan 'ach', nilai bulan yang sesuai review period ditimpa dengan nilai tersebut.
     */
    public static function achievementTracking(Goal $goal, array $appraisalForms = []): array
    {
        $goalData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);
        $goalAchievements = KPIAchievementService::getByGoal($goal->id);

        $savedKpi = collect($appraisalForms)->first(fn ($form) => ($form['formName'] ?? null) === 'KPI') ?? [];

        $tracking = [];

        foreach ($goalData ?? [] as $index => $kpi) {
            $kpiId = $kpi['kpi_id'] ?? null;
            $months = $kpiId && isset($goalAchievements[$kpiId]['ach'])
                ? $goalAchievements[$kpiId]['ach']
                : array_fill(1, 12, null);

            $saved = $savedKpi[$index]['ach'] ?? null;

            if (is_array($saved)) {
                foreach ($months as $month => $value) {
                    if (self::isTrackedMonth($month, $kpi['review_period'] ?? null)) {
                        $months[$month] = self::cleanMonthValue($saved[$month] ?? null);
                    }
                }
            }

            $tracking[$index] = $months;
        }

        return $tracking;
    }

    /**
     * File pendukung achievement per bulan per KPI: [index => [1..12 => path|null]], path relatif disk 'public'.
     *
     * Default dari file goal achievement. Kalau $appraisalForms punya KPI dengan 'ach_file', bulan yang
     * sesuai review period ditimpa dengan nilai tersebut (null = file sudah dihapus di appraisal).
     */
    public static function achievementFiles(Goal $goal, array $appraisalForms = []): array
    {
        $goalData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);
        $goalAchievements = KPIAchievementService::getByGoal($goal->id);

        $savedKpi = collect($appraisalForms)->first(fn ($form) => ($form['formName'] ?? null) === 'KPI') ?? [];

        $files = [];

        foreach ($goalData ?? [] as $index => $kpi) {
            $kpiId = $kpi['kpi_id'] ?? null;
            $months = $kpiId && isset($goalAchievements[$kpiId]['attachment'])
                ? $goalAchievements[$kpiId]['attachment']
                : array_fill(1, 12, null);

            $saved = $savedKpi[$index]['ach_file'] ?? null;

            if (is_array($saved)) {
                foreach ($months as $month => $value) {
                    if (self::isTrackedMonth($month, $kpi['review_period'] ?? null)) {
                        $months[$month] = is_string($saved[$month] ?? null) && $saved[$month] !== '' ? $saved[$month] : null;
                    }
                }
            }

            $files[$index] = $months;
        }

        return $files;
    }

    /**
     * Bulan yang bisa diisi sesuai review period (sama seperti halaman Update Achievement goal).
     */
    public static function isTrackedMonth(int $month, $reviewPeriod): bool
    {
        $period = (int) $reviewPeriod;

        return $period <= 1 || $month % $period === 0;
    }

    private static function cleanMonthValue($value): ?string
    {
        $value = str_replace(',', '', trim((string) $value));

        return is_numeric($value) ? (string) (float) $value : null;
    }

    /**
     * Actual satu KPI dari tracking bulanan, pakai calculation method KPI tersebut. Tanpa nilai = 0.
     */
    public static function trackingActual(array $kpi, array $months): float
    {
        $values = collect($months)
            ->sortKeys()
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->values()
            ->toArray();

        return empty($values)
            ? 0
            : round(self::aggregate($kpi['calculation_method'] ?? 'last', $values, $kpi['review_period'] ?? null), 2);
    }

    /**
     * Isi $goalData[i] dengan 'ach', 'actual' dan 'achievement_percent' untuk ditampilkan di form appraisal.
     */
    public static function withAchievementTracking(array $goalData, array $tracking, array $files = []): array
    {
        foreach ($goalData as $index => &$kpi) {
            $kpi['ach'] = $tracking[$index] ?? array_fill(1, 12, null);
            $kpi['ach_file'] = $files[$index] ?? array_fill(1, 12, null);
            $kpi['actual'] = self::trackingActual($kpi, $kpi['ach']);
            $kpi['achievement_percent'] = round(self::achievement(
                $kpi['actual'],
                (float) ($kpi['target'] ?? 0),
                $kpi['type'] ?? 'Higher Better'
            ), 2);
        }

        return $goalData;
    }

    /**
     * Tulis 'ach' dan 'achievement' (actual hasil hitung) ke form KPI yang akan disimpan.
     * Nilai achievement dari request diabaikan, selalu dihitung ulang dari tracking.
     */
    public static function applyAchievementTracking(array $formData, Goal $goal, array $tracking, ?array $files = null): array
    {
        $goalData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

        foreach ($formData as &$form) {
            if (($form['formName'] ?? null) !== 'KPI') {
                continue;
            }

            foreach ($goalData ?? [] as $index => $kpi) {
                $months = $tracking[$index] ?? array_fill(1, 12, null);
                $form[$index]['ach'] = $months;
                if ($files !== null) {
                    $form[$index]['ach_file'] = $files[$index] ?? array_fill(1, 12, null);
                }
                $form[$index]['achievement'] = (string) self::trackingActual($kpi, $months);
            }
        }

        return $formData;
    }

    // File pendukung achievement per bulan (Achievement Tracking KPI), sama aturannya dengan Update Achievement goal
    public const MONTHLY_FILE_RULES = [
        'kpi_attachment'          => 'nullable|array',
        'kpi_attachment.*'        => 'array',
        'kpi_attachment.*.*'      => 'nullable|file|mimes:pdf,png,jpg,jpeg,xlsx,xls,csv,docx,pptx|max:2048',
        'kpi_attachment_remove'   => 'nullable|array',
        'kpi_attachment_remove.*' => 'array',
    ];

    public const MONTHLY_FILE_MESSAGES = [
        'kpi_attachment.*.*.max'   => 'Monthly achievement file max 2MB.',
        'kpi_attachment.*.*.mimes' => 'Monthly achievement file must be PDF/JPG/PNG/XLSX/XLS/CSV/DOCX/PPTX.',
    ];

    /**
     * True kalau form KPI di request membawa tracking bulanan ('ach'), artinya halaman mengirim input yang bisa diedit.
     */
    public static function hasTrackingInput(array $formData): bool
    {
        $kpi = collect($formData)->first(fn ($form) => ($form['formName'] ?? null) === 'KPI') ?? [];

        return collect($kpi)->contains(fn ($item) => is_array($item) && is_array($item['ach'] ?? null));
    }

    /**
     * File achievement bulanan final per KPI: [index => [1..12 => path|null]].
     *
     * Dasar: file tersimpan di appraisal ($savedForms), fallback file goal achievement. Lalu diterapkan
     * kpi_attachment_remove[i][m] (hapus) dan kpi_attachment[i][m] (upload baru). Hanya bulan sesuai
     * review period. File goal tidak pernah dihapus dari disk karena masih dipakai halaman goal.
     */
    public static function monthlyAchievementFiles(Request $request, Goal $goal, array $savedForms, array $tracking, string $employeeId, $period): array
    {
        $goalData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);
        $files = self::achievementFiles($goal, $savedForms);
        $removes = $request->input('kpi_attachment_remove', []);
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

        foreach ($goalData ?? [] as $index => $kpi) {
            for ($month = 1; $month <= 12; $month++) {
                if (!self::isTrackedMonth($month, $kpi['review_period'] ?? null)) {
                    continue;
                }

                if (!empty($removes[$index][$month])) {
                    $files[$index][$month] = null;
                }

                $upload = $request->file("kpi_attachment.$index.$month");
                if (!($upload instanceof UploadedFile)) {
                    continue;
                }

                $value = $tracking[$index][$month] ?? null;
                if ($value === null || $value === '') {
                    throw ValidationException::withMessages([
                        "kpi_attachment.$index.$month" => 'KPI '.($index + 1).' - '.$monthNames[$month].': achievement value is required when uploading a file.',
                    ]);
                }

                $files[$index][$month] = $upload->store("appraisal-achievements/{$employeeId}/{$period}/{$index}", 'public');
            }
        }

        return $files;
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

    private static function calculateAverage(array $values, ?int $reviewPeriod): float
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