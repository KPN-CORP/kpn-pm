<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Pembaca resources/goal.json yang di-memoize per request.
 *
 * Sebelumnya beberapa tempat membaca file ini DI DALAM loop KPI
 * (ReportController + AchievementReportExport), jadi untuk 500 goal x 5 KPI
 * ada ~5.000 kali File::exists + File::get + json_decode untuk file statis
 * yang sama. Sekarang dibaca sekali per request.
 */
class GoalOptions
{
    private static ?array $options = null;

    private static ?array $reviewPeriodLabels = null;

    private static ?array $calculationMethodLabels = null;

    public static function path(): string
    {
        return base_path('resources/goal.json');
    }

    /**
     * Seluruh isi goal.json. Dibaca sekali, lalu dipakai ulang.
     */
    public static function all(): array
    {
        if (self::$options !== null) {
            return self::$options;
        }

        $path = self::path();

        if (! File::exists($path)) {
            throw new RuntimeException('JSON file does not exist: '.$path);
        }

        return self::$options = json_decode(File::get($path), true) ?? [];
    }

    public static function get(string $key, array $default = []): array
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Map value => label untuk "Review Period".
     */
    public static function reviewPeriodLabels(): array
    {
        return self::$reviewPeriodLabels ??= self::labelMap('Review Period');
    }

    /**
     * Map value => label untuk "Calculation Method".
     */
    public static function calculationMethodLabels(): array
    {
        return self::$calculationMethodLabels ??= self::labelMap('Calculation Method');
    }

    private static function labelMap(string $key): array
    {
        return collect(self::get($key))
            ->flatten(1)
            ->pluck('label', 'value')
            ->toArray();
    }

    /**
     * Dipakai di test / long-running worker untuk memaksa baca ulang.
     */
    public static function flush(): void
    {
        self::$options = null;
        self::$reviewPeriodLabels = null;
        self::$calculationMethodLabels = null;
    }
}
