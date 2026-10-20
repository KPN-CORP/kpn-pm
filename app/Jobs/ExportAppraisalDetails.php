<?php

namespace App\Jobs;

use App\Exports\AppraisalDetailExport;
use App\Exports\AutoSizedAppraisalDetailExport;
use App\Services\AppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ExportAppraisalDetails implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected array $data;
    protected array $headers;
    protected AppService $appService;
    protected int $userId;
    protected int $batchSize;
    protected string $jobTrackingId;
    protected $user;
    protected $period;

    public $timeout = 3600;

    public function __construct(AppService $appService, array $data, array $headers, int $userId, int $batchSize = 100, $user, $period)
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->appService = $appService;
        $this->userId = $userId;
        $this->batchSize = $batchSize;
        $this->jobTrackingId = 'export_appraisal_reports_' . $userId;
        $this->user = $user;
        $this->period = $period;
    }

    public function handle()
    {
        try {
            ini_set('memory_limit', '1G');

            $this->setProgress('processing', 0);

            $period = $this->period ?? $this->appService->appraisalPeriod();
            $fileName = 'exports/appraisal_details_' . $this->userId . '.xlsx';
            $totalRows = count($this->data);

            Log::info($this->userId . ' Creating appraisal detail export: ' . $fileName, ['rows' => $totalRows]);

            // Dulu data dipecah per $batchSize: tiap batch ditulis ke file
            // xlsx sementara, lalu SEMUA file itu dibaca ulang dan disalin sel
            // per sel ke workbook baru. Tulis-baca-tulis ulang itu memakan
            // sebagian besar waktu export, dan header dinamis (KPI/Culture/
            // Leadership) dari batch pertama dipakai untuk semua batch,
            // sehingga kolom batch berikutnya bisa bergeser. Sekarang satu
            // export langsung untuk semua baris: header-nya dihitung dari
            // seluruh data dan file hanya ditulis sekali.
            //
            // Progress: pemrosesan data 0-30%, menulis baris ke sheet 30-90%,
            // sisanya menyimpan file xlsx.
            $onProgress = function (string $phase, int $done, int $total) {
                $ratio = $done / max($total, 1);
                $percent = $phase === 'expand' ? $ratio * 30 : 30 + $ratio * 60;

                $this->setProgress('processing', (int) floor($percent));
            };

            // Lebar kolom otomatis mahal untuk sheet besar. Sejak dulu hanya
            // export kecil (satu batch) yang auto-size — hasil gabungan batch
            // tidak pernah — jadi perilaku itu dipertahankan.
            $export = $totalRows <= $this->batchSize
                ? new AutoSizedAppraisalDetailExport($this->appService, $this->data, $this->headers, $this->user, $period, $onProgress)
                : new AppraisalDetailExport($this->appService, $this->data, $this->headers, $this->user, $period, $onProgress);

            // PhpSpreadsheet membuat satu objek per sel (ratusan ribu untuk
            // report besar). Cycle GC PHP terus memindai ulang objek-objek itu
            // selama penulisan sehingga waktunya tumbuh lebih dari linear;
            // dimatikan sementara selama menulis file (~2.4x lebih cepat
            // untuk 6.300 baris).
            $gcWasEnabled = gc_enabled();
            gc_disable();

            try {
                Excel::store($export, $fileName, 'public');
            } finally {
                if ($gcWasEnabled) {
                    gc_enable();
                }
            }

            $this->setProgress('completed', 100);

        } catch (\Exception $e) {
            Log::error("ExportAppraisalDetails failed: " . $e->getMessage());
            throw $e; // Rethrow to mark the job as failed
        }

    }

    /**
     * Cache key the frontend polls to render the progress bar.
     */
    protected function progressKey(): string
    {
        return 'export_appraisal_progress_' . $this->userId;
    }

    protected function setProgress(string $status, int $percent): void
    {
        Cache::put($this->progressKey(), [
            'status'  => $status,
            'percent' => max(0, min(100, $percent)),
        ], now()->addHour());
    }

    /**
     * Runs after the final retry fails; mark the progress as failed so the UI
     * can stop the spinner and tell the user to try again.
     */
    public function failed(\Throwable $e): void
    {
        Cache::put($this->progressKey(), [
            'status'  => 'failed',
            'percent' => 0,
            'message' => 'Report generation failed. Please try again.',
        ], now()->addHour());

        Log::error('ExportAppraisalDetails permanently failed: ' . $e->getMessage());
    }

    public function tags()
    {
        // Optional: Add tags for easier tracking (visible in Horizon if used)
        return ['user:' . $this->userId, 'job:' . $this->jobTrackingId];
    }

    public $tries = 3;
}