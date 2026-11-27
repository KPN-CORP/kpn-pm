<?php

namespace App\Support;

use App\Services\AppService;
use Illuminate\Support\Facades\DB;

/**
 * Periode yang dipakai saat membaca/menulis approval_layer_appraisals.
 *
 * Default = periode appraisal berjalan (AppService::appraisalPeriod, sama
 * dengan periode yang dipakai approval_requests/appraisals). Global scope di
 * ApprovalLayerAppraisal memakai current(), jadi semua query layer yang ada
 * otomatis hanya melihat layer periode berjalan.
 *
 * Halaman yang menampilkan periode lalu (filterYear) memanggil view($period)
 * supaya layer yang dipakai ikut periode itu. Binding-nya "scoped" (lihat
 * AppServiceProvider), sehingga override di-reset per request / per job
 * queue dan tidak bocor ke proses berikutnya.
 */
class AppraisalLayerPeriod
{
    private ?int $override = null;

    /** @var array<int, int>|null */
    private ?array $available = null;

    private static function instance(): self
    {
        return app(self::class);
    }

    /** Periode appraisal berjalan (tanpa override). */
    public static function active(): ?int
    {
        $period = app(AppService::class)->appraisalPeriod();

        return $period ? (int) $period : null;
    }

    /** Periode yang sedang dipakai query layer. */
    public static function current(): ?int
    {
        return self::instance()->override ?? self::active();
    }

    /**
     * Pakai layer periode $period untuk sisa request ini (halaman riwayat).
     * Bila periode itu belum punya layer sama sekali (mis. 2024, sebelum
     * layer per periode ada), dipakai periode terdekat sebelumnya; kalau
     * tidak ada juga, periode tertua yang ada. Periode berjalan selalu
     * dipakai apa adanya walau masih kosong.
     */
    public static function view($period): ?int
    {
        $period = $period ? (int) $period : null;
        $active = self::active();

        if (! $period || $period === $active) {
            self::instance()->override = null;

            return $active;
        }

        $available = self::available();
        $resolved = in_array($period, $available, true)
            ? $period
            : (collect($available)->filter(fn ($p) => $p < $period)->max() ?? collect($available)->min() ?? $period);

        self::instance()->override = $resolved;

        return $resolved;
    }

    /** Jalankan $callback dengan layer periode $period, lalu kembalikan seperti semula. */
    public static function using($period, callable $callback)
    {
        $instance = self::instance();
        $previous = $instance->override;
        $instance->override = $period ? (int) $period : null;

        try {
            return $callback();
        } finally {
            $instance->override = $previous;
        }
    }

    /**
     * Periode yang sudah punya layer, menurun.
     *
     * @return array<int, int>
     */
    public static function available(): array
    {
        $instance = self::instance();

        return $instance->available ??= DB::table('approval_layer_appraisals')
            ->whereNotNull('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period')
            ->map(fn ($p) => (int) $p)
            ->all();
    }

    public static function forgetAvailable(): void
    {
        self::instance()->available = null;
    }
}
