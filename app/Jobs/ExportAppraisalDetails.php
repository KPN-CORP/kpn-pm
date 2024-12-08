<?php

namespace App\Jobs;

use App\Exports\AppraisalDetailExport;
use App\Services\AppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class ExportAppraisalDetails implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected array $data;
    protected array $headers;
    protected AppService $appService;
    protected int $userId;

    public $timeout = 3600; // Set timeout for the job (1 hour for large exports)

    /**
     * Create a new job instance.
     *
     * @param  AppService  $appService
     * @param  array  $data
     * @param  array  $headers
     */
    public function __construct(AppService $appService, array $data, array $headers, int $userId)
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->appService = $appService;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // $fileName = 'exports/appraisal_details_' . now()->timestamp . '.xlsx';
        $fileName = 'exports/appraisal_details_' . $this->userId . '.xlsx';
        // $fileName = 'exports/appraisal_details_' . $this->appService->userID() . '.xlsx';

        Log::info('Export started for: ' . $fileName);

        // The AppraisalDetailExport logic remains unchanged.
        $export = new AppraisalDetailExport($this->appService, $this->data, $this->headers);
        
        // Use Excel facade to generate and store the export (e.g., in a file or storage)
        // Excel::store($export, 'exports/appraisal_details.xlsx');
        Excel::store($export, $fileName);

        return response()->json([
            'message' => 'Export completed',
            'file_url' => asset('storage/' . $fileName), // Use Laravel's asset() function for the public URL
        ]);
    }

    public $tries = 3;
}
