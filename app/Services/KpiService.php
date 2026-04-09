<?php 
namespace App\Services;

class KPIService
{
    public static function aggregate($method, $values)
    {
        if (empty($values)) return 0;

        return match ($method) {
            'average' => array_sum($values) / count($values),
            'sum'     => array_sum($values),
            'last'    => end($values),
            'max'     => max($values),
            'min'     => min($values),
            default   => 0,
        };
    }

    public static function achievement($actual, $target, $type)
    {
        if ($target == 0) return 0;

        return match ($type) {
            'Higher Better' => ($actual / $target) * 100,
            'Lower Better'  => ($target / max($actual, 1)) * 100,
            'Exact Value'   => $actual == $target ? 100 : 0,
            default         => 0,
        };
    }

    public static function normalize($score)
    {
        return min(max($score, 0), 150); // cap 150%
    }

    public static function finalScore($normalized, $weight)
    {
        return ($normalized * $weight) / 100;
    }
}