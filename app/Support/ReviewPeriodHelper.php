<?php

namespace App\Support;

class ReviewPeriodHelper
{
    public static function shouldRemind(string $reviewPeriod): bool
    {
        $month = now()->month;

        return match ($reviewPeriod) {

            '1','monthly' => true,

            '2','bi-monthly' => in_array($month, [2, 4, 6, 8, 10, 12]),

            '3','quarterly' => in_array($month, [3, 6, 9, 12]),

            '6','semester' => in_array($month, [6, 12]),

            '12','annual' => $month === 12,

            default => false,
        };
    }

    public static function isAchievementExpired(
        ?string $reviewPeriod,
        $lastAchievementAt
    ): bool {

        if (!$lastAchievementAt) {
            return true;
        }

        $date = \Carbon\Carbon::parse($lastAchievementAt);

        return match ($reviewPeriod) {

            '1','monthly' => $date->lt(now()->startOfMonth()),

            '2','bi-monthly' => $date->lt(now()->subMonths(2)),

            '3','quarterly' => $date->lt(now()->subMonths(3)),

            '6','semesterly' => $date->lt(now()->subMonths(6)),

            '12','annual' => $date->lt(now()->subYear()),

            default => true,
        };
    }
}