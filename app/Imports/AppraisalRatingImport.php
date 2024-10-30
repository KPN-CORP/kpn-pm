<?php

namespace App\Imports;

use App\Exports\InvalidAppraisalRatingImport;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\KpiUnits;
use App\Models\MasterRating;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class AppraisalRatingImport implements ToCollection, WithHeadingRow
{
    protected $userId;
    protected $invalidEmployees = [];
    protected $usedCombinations = [];
    protected $invalidEmployeeIds = [];
    protected $employeeIds = [];
    protected $appService;
    protected $period;


    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->appService = new AppService();
        $this->period = 2024;

    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function collection(Collection $collection)
    {
        $headers = $collection->first()->keys();

        // Define the expected headers in the correct order
        $expectedHeaders = ['Employee_ID', 'Approver_Rating_ID', 'Your_Rating'];

        // Check if the headers match exactly in the right order
        if (collect($expectedHeaders)->diff($headers)->isEmpty()) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain Employee_ID, Approver_Rating_ID, Your_Rating'
            ]);
        }

        foreach ($collection as $row) {

            // Debug each row of data
            $allowedRating = ['A','B','C'];

            if (!in_array($row['your_rating'], $allowedRating)) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'your_rating' => $row['your_rating'],
                    'message' => 'Your Rating type not found.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
            
        }
        
        // Remove duplicates from invalid employee IDs list
        $this->invalidEmployeeIds = array_unique($this->invalidEmployeeIds);

        if (!in_array($row['employee_id'], $this->invalidEmployeeIds)) {

            // Second pass to process and import data
            foreach ($collection as $row) {  

                // Create a new Appraisal instance and save the data
            $calibration = Calibration::with(['masterCalibration'])->where('employee_id', $row['employee_id'])
                            ->where('approver_id', $row['approver_rating_id'])
                            ->where('status', 'Pending')
                            ->first();

            $id_rating = $calibration->masterCalibration->first()->id_rating_group;

            $ratings = MasterRating::select('parameter', 'value')
            ->where('id_rating_group', $id_rating)
            ->get();

            $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
            
            $convertedValue = $ratingMap[$row['your_rating']] ?? null;
            
            $updated = Calibration::where('approver_id', $row['approver_rating_id'])
                ->where('employee_id', $row['employee_id'])
                ->where('appraisal_id', $calibration->appraisal_id)
                ->where('period', $this->period)
                ->update([
                    'rating' => $convertedValue,
                    'status' => 'Approved',
                    'updated_by' => Auth()->user()->id
                ]);

                // Update Nilai Rating
                if ($updated) {

                    $nextApprover = $this->appService->processApproval($row['employee_id'], $row['approver_rating_id']);

                    if ($nextApprover['next_approver_id']) {
                        # code...
                        $createCalibration = new Calibration();
                        $createCalibration->id_calibration_group = $calibration->id_calibration_group;
                        $createCalibration->appraisal_id = $calibration->appraisal_id;
                        $createCalibration->employee_id = $row['employee_id'];
                        $createCalibration->approver_id = $nextApprover['next_approver_id'];
                        $createCalibration->period = $this->period;
                        $createCalibration->created_by = Auth()->user()->id;
                        $createCalibration->save();
                    }
                }
            
            }
            
        }
    }
    
    public function exportInvalidEmployees()
    {
        if (!empty($this->invalidEmployees)) {
            // Export the invalid employees with error messages to an Excel file
            return Excel::download(new InvalidAppraisalRatingImport($this->invalidEmployees), 'errors_rating_import.xlsx');

        }
        return null;
    }

    // Return the list of invalid employees with error messages
    public function getInvalidEmployees()
    {
        return $this->invalidEmployees;
    }
}
