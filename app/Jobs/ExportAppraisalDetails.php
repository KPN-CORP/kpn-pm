<?php

namespace App\Jobs;

use App\Exports\AppraisalDetailExport;
use App\Services\AppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportAppraisalDetails implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected array $data;
    protected array $headers;
    protected AppService $appService;
    protected int $userId;
    protected int $batchSize;

    public $timeout = 3600;

    public function __construct(AppService $appService, array $data, array $headers, int $userId, int $batchSize = 500)
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->appService = $appService;
        $this->userId = $userId;
        $this->batchSize = $batchSize;
    }

    public function handle()
    {
        // If data count is less than or equal to batch size, create single file
        if (count($this->data) <= $this->batchSize) {
            $fileName = 'exports/appraisal_details_' . $this->userId . '.xlsx';
            Log::info('Creating single Excel file: ' . $fileName);

            $export = new AppraisalDetailExport($this->appService, $this->data, $this->headers);
            Excel::store($export, $fileName, 'public');

            return;
        }

        // For data exceeding batch size, create multiple files and zip them
        $batches = array_chunk($this->data, $this->batchSize);
        $tempFiles = [];

        Log::info('Starting export with ' . count($batches) . ' batches');

        // Create temp directory if it doesn't exist
        $tempDir = storage_path('app/public/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create individual Excel files
        foreach ($batches as $index => $batchData) {
            $batchNumber = $index + 1;
            $tempFileName = "temp/batch_{$batchNumber}.xlsx";
            $tempFiles[] = $tempFileName;

            Log::info('Processing batch ' . $batchNumber);

            $export = new AppraisalDetailExport($this->appService, $batchData, $this->headers);
            Excel::store($export, $tempFileName, 'public');
        }

        // Create ZIP file
        $zipFileName = 'exports/appraisal_details_' . $this->userId . '.zip';
        $zip = new ZipArchive();

        if ($zip->open(storage_path('app/public/' . $zipFileName), ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($tempFiles as $index => $tempFile) {
                $batchNumber = $index + 1;
                $zip->addFile(
                    storage_path('app/public/' . $tempFile),
                    "appraisal_details_batch_{$batchNumber}.xlsx"
                );
            }
            $zip->close();

            // Clean up temp files
            foreach ($tempFiles as $tempFile) {
                Storage::disk('public')->delete($tempFile);
            }

            Log::info('ZIP file created successfully: ' . $zipFileName);
        } else {
            Log::error('Failed to create ZIP file');
            throw new \Exception('Failed to create ZIP file');
        }
    }

    public $tries = 3;
}
