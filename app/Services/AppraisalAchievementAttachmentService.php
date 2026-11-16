<?php

namespace App\Services;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Attachment achievement per bulan di form appraisal (grid Achievement Tracking).
 *
 * Attachment default diambil dari goal achievement (folder "kpi-achievements/...", milik
 * halaman Update Achievement) sehingga TIDAK PERNAH dihapus dari sini; file yang diunggah
 * dari form appraisal disimpan di folder sendiri dan hanya file itu yang boleh dibuang.
 */
class AppraisalAchievementAttachmentService
{
    /** name="achAttachment[kpiIndex][month]" */
    public const INPUT = 'achAttachment';

    /** name="removeAchAttachment[kpiIndex][month]" */
    public const REMOVE_INPUT = 'removeAchAttachment';

    /** Folder di disk 'public' untuk file yang diunggah dari form appraisal. */
    private const OWNED_DIR = 'appraisal-achievements';

    /** Batas ukuran per file, sama seperti halaman Update Achievement. */
    public const MAX_KILOBYTES = 2048;

    public const ACCEPTED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,jpg,jpeg,png';

    /**
     * Rule validasi untuk digabung ke $request->validate() di controller.
     */
    public static function validationRules(): array
    {
        return [
            self::INPUT              => 'nullable|array',
            self::INPUT . '.*.*'     => 'nullable|file|mimes:' . self::ACCEPTED_MIMES . '|max:' . self::MAX_KILOBYTES,
            self::REMOVE_INPUT       => 'nullable|array',
        ];
    }

    /**
     * Gabungkan attachment yang sudah ada dengan file baru / permintaan hapus dari request.
     *
     * @param  array  $current  hasil KPIService::attachmentTracking(): [kpiIndex => [1..12 => path|null]]
     * @return array{attachments: array, stored: array<int, string>, stale: array<int, string>}
     *         attachments = nilai baru untuk disimpan ke form_data,
     *         stored      = file yang baru tersimpan (hapus lagi kalau transaksi gagal),
     *         stale       = file lama milik appraisal yang sudah tidak dipakai (hapus setelah sukses).
     */
    public static function resolve(Request $request, Goal $goal, array $current): array
    {
        $uploads  = $request->file(self::INPUT) ?? [];
        $removals = $request->input(self::REMOVE_INPUT) ?? [];

        $attachments = $current;
        $stored      = [];
        $stale       = [];

        foreach ($attachments as $index => $months) {
            if (!is_array($months)) {
                continue;
            }

            foreach ($months as $month => $path) {
                $file = $uploads[$index][$month] ?? null;
                $file = $file instanceof UploadedFile && $file->isValid() ? $file : null;

                if ($file) {
                    $newPath = $file->store(self::OWNED_DIR . "/{$goal->id}/{$index}", 'public');

                    $stored[] = $newPath;
                    $attachments[$index][$month] = $newPath;

                    if (self::isOwned($path)) {
                        $stale[] = $path;
                    }

                    continue;
                }

                if (!empty($removals[$index][$month])) {
                    if (self::isOwned($path)) {
                        $stale[] = $path;
                    }

                    $attachments[$index][$month] = null;
                }
            }
        }

        return [
            'attachments' => $attachments,
            'stored'      => $stored,
            'stale'       => $stale,
        ];
    }

    /**
     * Hapus file milik form appraisal. Dipakai untuk membuang file lama setelah simpan berhasil,
     * atau membuang file yang baru diunggah kalau simpan gagal.
     *
     * @param  array<int, string|null>  $paths
     */
    public static function delete(array $paths): void
    {
        foreach ($paths as $path) {
            if (self::isOwned($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private static function isOwned(?string $path): bool
    {
        return is_string($path) && $path !== '' && Str::startsWith($path, self::OWNED_DIR . '/');
    }
}
