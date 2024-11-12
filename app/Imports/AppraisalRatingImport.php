<?php

namespace App\Imports;

use App\Exports\InvalidAppraisalRatingImport;
use App\Models\Appraisal;
use App\Models\Calibration;
use App\Models\MasterRating;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
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
    protected $allowedRating = [];

    public function __construct($userId, $allowedRating)
    {
        $this->userId = $userId;
        $this->allowedRating = $allowedRating;
        $this->appService = new AppService();
        $this->period = 2024;
    }

    public function collection(Collection $collection)
    {
        $headers = $collection->first()->keys();

        // Define the expected headers in the correct order
        $expectedHeaders = ['Employee_ID', 'Approver_Rating_ID', 'Your_Rating'];

        // Check if headers are valid
        if (collect($expectedHeaders)->diff($headers)->isEmpty()) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain Employee_ID, Approver_Rating_ID, Your_Rating'
            ]);
        }

        $isRatingValid = true;
        $countRow = 0;
        $firstConvertedValue = null;

        foreach ($collection as $row) {
            $countRow++;
            // Validate 'Your_Rating' values
            if (!in_array($row['your_rating'], $this->allowedRating)) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_rating_id'],
                    'your_rating' => $row['your_rating'],
                    'message' => 'Your Rating type not found.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id'];
                $isRatingValid = false;
                continue; // Skip this row since it failed validation
            }

            // Retrieve calibration for each employee-approver pair
            $calibration = Calibration::with(['masterCalibration'])
                ->where('employee_id', $row['employee_id'])
                ->where('approver_id', $row['approver_rating_id'])
                ->where('status', 'Pending')
                ->first();

            if ($calibration) {
                $id_rating = $calibration->masterCalibration->first()->id_rating_group;
                $ratings = MasterRating::select('parameter', 'value')
                    ->where('id_rating_group', $id_rating)
                    ->get();

                $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
                $convertedValue = $ratingMap[$row['your_rating']] ?? null;
                
                // Additional condition: if only one row is being processed, convertedValue should not be 5
                if ($countRow == 1) {
                    if ($convertedValue == 5) {
                        $this->invalidEmployees[] = [
                            'employee_id' => $row['employee_id'],
                            'approver_id' => $row['approver_rating_id'],
                            'your_rating' => $row['your_rating'],
                            'message' => 'Rating mismatch detected. Please correct the ratings to match the expected values.'
                        ];
                        $isRatingValid = false;
                        continue; // Skip further processing for this row
                    }
                    $firstConvertedValue = $convertedValue; // Store the first row's convertedValue
                }

                // Rule for countRow == 2: convertedValue cannot be the same as first row's convertedValue
                if ($countRow == 2 && $convertedValue === $firstConvertedValue) {
                    $this->invalidEmployees[] = [
                        'employee_id' => $row['employee_id'],
                        'approver_id' => $row['approver_rating_id'],
                        'your_rating' => $row['your_rating'],
                        'message' => 'Rating mismatch detected. the selected rating will be available to be allocated only 1 time. Please correct the ratings to match the expected values.'
                    ];
                    $isRatingValid = false;
                    continue; // Skip further processing for this row
                }
            }
        }

        // Only process rows that passed initial validation
        if ($isRatingValid) {
            foreach ($collection as $row) {
                if (in_array($row['employee_id'], $this->invalidEmployeeIds)) {
                    continue;
                }
                $calibration = Calibration::with(['masterCalibration'])
                ->where('employee_id', $row['employee_id'])
                ->where('approver_id', $row['approver_rating_id'])
                ->where('status', 'Pending')
                ->first();
                $id_rating = $calibration->masterCalibration->first()->id_rating_group;
                $ratings = MasterRating::select('parameter', 'value')
                    ->where('id_rating_group', $id_rating)
                    ->get();

                $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
                $convertedValue = $ratingMap[$row['your_rating']] ?? null;

                // Update Calibration data based on matching employee and approver IDs
                $updated = Calibration::where('approver_id', $row['approver_rating_id'])
                    ->where('employee_id', $row['employee_id'])
                    ->where('period', $this->period)
                    ->update([
                        'rating' => $convertedValue,
                        'status' => 'Approved',
                        'updated_by' => Auth::id()
                    ]);

                if ($updated) {
                    $nextApprover = $this->appService->processApproval($row['employee_id'], $row['approver_rating_id']);
                    if ($nextApprover) {
                        $createCalibration = new Calibration();
                        $createCalibration->id_calibration_group = $calibration->id_calibration_group;
                        $createCalibration->appraisal_id = $calibration->appraisal_id;
                        $createCalibration->employee_id = $row['employee_id'];
                        $createCalibration->approver_id = $nextApprover['next_approver_id'];
                        $createCalibration->period = $this->period;
                        $createCalibration->created_by = Auth::id();
                        $createCalibration->save();
                    } else {
                        Appraisal::where('id', $calibration->appraisal_id)
                            ->update([
                                'rating' => $convertedValue,
                                'form_status' => 'Approved',
                                'updated_by' => Auth::id()
                            ]);
                    }
                }
            }
        }
    }

    public function exportInvalidEmployees()
    {
        if (!empty($this->invalidEmployees)) {
            return Excel::download(new InvalidAppraisalRatingImport($this->invalidEmployees), 'errors_rating_import.xlsx');
        }
        return null;
    }

    public function getInvalidEmployees()
    {
        return $this->invalidEmployees;
    }
}
