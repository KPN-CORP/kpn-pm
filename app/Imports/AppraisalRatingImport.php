<?php

namespace App\Imports;

use App\Exports\InvalidAppraisalRatingImport;
use App\Models\Appraisal;
use App\Models\Calibration;
use App\Models\MasterRating;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // public function collection(Collection $collection)
    // {
    //     $headers = $collection->first()->keys();

    //     // Define the expected headers in the correct order
    //     $expectedHeaders = ['Employee_ID', 'Approver_Rating_ID', 'Your_Rating'];

    //     // Check if headers are valid
    //     if (collect($expectedHeaders)->diff($headers)->isEmpty()) {
    //         throw ValidationException::withMessages([
    //             'error' => 'Invalid excel format. The header must contain Employee_ID, Approver_Rating_ID, Your_Rating'
    //         ]);
    //     }

    //     $isRatingValid = true;
    //     $countRow = 0;
    //     $firstConvertedValue = null;

    //     foreach ($collection as $row) {
    //         $countRow++;
    //         // Validate 'Your_Rating' values
    //         if (!in_array($row['your_rating'], $this->allowedRating)) {
    //             $this->invalidEmployees[] = [
    //                 'employee_id' => $row['employee_id'],
    //                 'approver_id' => $row['approver_rating_id'],
    //                 'your_rating' => $row['your_rating'],
    //                 'message' => 'Your Rating type not found.'
    //             ];
    //             $this->invalidEmployeeIds[] = $row['employee_id'];
    //             $isRatingValid = false;
    //             continue; // Skip this row since it failed validation
    //         }

    //         // Retrieve calibration for each employee-approver pair
    //         $calibration = Calibration::with(['masterCalibration'])
    //             ->where('employee_id', $row['employee_id'])
    //             ->where('approver_id', $row['approver_rating_id'])
    //             ->where('status', 'Pending')
    //             ->first();

    //         if ($calibration) {
    //             $id_rating = $calibration->masterCalibration->first()->id_rating_group;
    //             $ratings = MasterRating::select('parameter', 'value')
    //                 ->where('id_rating_group', $id_rating)
    //                 ->get();

    //             $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
    //             $convertedValue = $ratingMap[$row['your_rating']] ?? null;
                
    //             // Additional condition: if only one row is being processed, convertedValue should not be 5
    //             if ($countRow == 1) {
    //                 if ($convertedValue == 5) {
    //                     $this->invalidEmployees[] = [
    //                         'employee_id' => $row['employee_id'],
    //                         'approver_id' => $row['approver_rating_id'],
    //                         'your_rating' => $row['your_rating'],
    //                         'message' => 'Rating mismatch detected. Please correct the ratings to match the expected values.'
    //                     ];
    //                     $isRatingValid = false;
    //                     continue; // Skip further processing for this row
    //                 }
    //                 $firstConvertedValue = $convertedValue; // Store the first row's convertedValue
    //             }

    //             // Rule for countRow == 2: convertedValue cannot be the same as first row's convertedValue
    //             if ($countRow == 2 && $convertedValue === $firstConvertedValue) {
    //                 $this->invalidEmployees[] = [
    //                     'employee_id' => $row['employee_id'],
    //                     'approver_id' => $row['approver_rating_id'],
    //                     'your_rating' => $row['your_rating'],
    //                     'message' => 'Rating mismatch detected. the selected rating will be available to be allocated only 1 time. Please correct the ratings to match the expected values.'
    //                 ];
    //                 $isRatingValid = false;
    //                 continue; // Skip further processing for this row
    //             }
    //         }
    //     }

    //     // Only process rows that passed initial validation
    //     if ($isRatingValid) {
    //         foreach ($collection as $row) {
    //             if (in_array($row['employee_id'], $this->invalidEmployeeIds)) {
    //                 continue;
    //             }
    //             $calibration = Calibration::with(['masterCalibration'])
    //             ->where('employee_id', $row['employee_id'])
    //             ->where('approver_id', $row['approver_rating_id'])
    //             ->where('status', 'Pending')
    //             ->first();
    //             $id_rating = $calibration->masterCalibration->first()->id_rating_group;
    //             $ratings = MasterRating::select('parameter', 'value')
    //                 ->where('id_rating_group', $id_rating)
    //                 ->get();

    //             $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
    //             $convertedValue = $ratingMap[$row['your_rating']] ?? null;

    //             // Update Calibration data based on matching employee and approver IDs
    //             $updated = Calibration::where('approver_id', $row['approver_rating_id'])
    //                 ->where('employee_id', $row['employee_id'])
    //                 ->where('period', $this->period)
    //                 ->update([
    //                     'rating' => $convertedValue,
    //                     'status' => 'Approved',
    //                     'updated_by' => Auth::id()
    //                 ]);

    //             if ($updated) {
    //                 $nextApprover = $this->appService->processApproval($row['employee_id'], $row['approver_rating_id']);
    //                 if ($nextApprover) {
    //                     $createCalibration = new Calibration();
    //                     $createCalibration->id_calibration_group = $calibration->id_calibration_group;
    //                     $createCalibration->appraisal_id = $calibration->appraisal_id;
    //                     $createCalibration->employee_id = $row['employee_id'];
    //                     $createCalibration->approver_id = $nextApprover['next_approver_id'];
    //                     $createCalibration->period = $this->period;
    //                     $createCalibration->created_by = Auth::id();
    //                     $createCalibration->save();
    //                 } else {
    //                     Appraisal::where('id', $calibration->appraisal_id)
    //                         ->update([
    //                             'rating' => $convertedValue,
    //                             'form_status' => 'Approved',
    //                             'updated_by' => Auth::id()
    //                         ]);
    //                 }
    //             }
    //         }
    //     }
    // }

    public function collection(Collection $collection)
    {
        // Validate headers (keeping existing validation)
        $headers = $collection->first()->keys();
        $expectedHeaders = ['Employee_ID', 'Approver_Rating_ID', 'Your_Rating'];
        if (collect($expectedHeaders)->diff($headers)->isEmpty()) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain Employee_ID, Approver_Rating_ID, Your_Rating'
            ]);
        }

        $firstConvertedValue = null;

        $isRatingValid = true;
        $totalRows = $collection->count();
        
        // Initialize rating counts
        $ratingCounts = [
            5 => 0, // firstRow (A)
            4 => 0, // secondRow (B)
            3 => 0, // thirdRow (C)
            2 => 0, // fourthRow (D)
            1 => 0  // fifthRow (E)
        ];

        // First pass: Count ratings and validate basic rules
        foreach ($collection as $index => $row) {
            // Skip invalid ratings
            if (!in_array($row['your_rating'], $this->allowedRating)) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_rating_id'],
                    'your_rating' => $row['your_rating'],
                    'message' => 'Your Rating type not found.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id'];
                $isRatingValid = false;
                continue;
            }

            $calibration = Calibration::with(['masterCalibration'])
                ->where('employee_id', $row['employee_id'])
                ->where('approver_id', $row['approver_rating_id'])
                ->where('status', 'Pending')
                ->first();

            if (!$calibration) continue;

            $id_rating = $calibration->masterCalibration->first()->id_rating_group;
            $ratings = MasterRating::select('parameter', 'value')
                ->where('id_rating_group', $id_rating)
                ->get();

            $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
            $convertedValue = $ratingMap[$row['your_rating']] ?? null;
            
            // Count each rating
            if ($convertedValue) {
                $ratingCounts[$convertedValue]++;
            }

            // Validation for single employee
            if ($totalRows === 1 && $convertedValue === 5) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_rating_id'],
                    'your_rating' => $row['your_rating'],
                    'message' => 'Rating A is not allowed for single employee.'
                ];
                $isRatingValid = false;
                continue;
            }

            // Validation for two employees
            if ($totalRows === 2) {
                if ($index === 1 && $convertedValue === $firstConvertedValue) {
                    $this->invalidEmployees[] = [
                        'employee_id' => $row['employee_id'],
                        'approver_id' => $row['approver_rating_id'],
                        'your_rating' => $row['your_rating'],
                        'message' => 'Duplicate ratings are not allowed for two employees.'
                    ];
                    $isRatingValid = false;
                    continue;
                }
            }

            if ($index === 0) {
                $firstConvertedValue = $convertedValue;
            }
        }

        // Calculate targets
        $thirdRowMaxTarget = $ratingCounts[5] + $ratingCounts[4] + $ratingCounts[3];
        $fourthRowMaxTarget = $ratingCounts[2] + $ratingCounts[1];

        // Second pass: Validate quota rules
        if ($isRatingValid) {
            foreach ($collection as $index => $row) {
                if (in_array($row['employee_id'], $this->invalidEmployeeIds)) continue;

                $calibration = $this->getCalibrationsWithRating($row);
                if (!$calibration) continue;

                $convertedValue = $this->getConvertedValue($calibration, $row['your_rating']);
                if (!$convertedValue) continue;

                // Validate quotas based on rating value
                if (in_array($convertedValue, [5, 4]) && $ratingCounts[$convertedValue] > $this->getQuota($convertedValue)) {
                    $this->addInvalidEmployee($row, 'The following ratings do not meet the expected Target.');
                    $isRatingValid = false;
                    continue;
                }

                if ($convertedValue === 3 && $ratingCounts[3] > $thirdRowMaxTarget) {
                    $this->addInvalidEmployee($row, 'The following ratings do not meet the expected Target.');
                    $isRatingValid = false;
                    continue;
                }

                if ($convertedValue === 2 && $ratingCounts[2] < $this->getMinimumQuota(2)) {
                    $this->addInvalidEmployee($row, 'The following ratings do not meet the expected Target.');
                    $isRatingValid = false;
                    continue;
                }
            }
        }

        // Process valid records
        if ($isRatingValid) {
            $this->processValidRecords($collection);
        }
    }

    // Helper methods (add these to your class)
    private function getCalibrationsWithRating($row)
    {
        return Calibration::with(['masterCalibration'])
            ->where('employee_id', $row['employee_id'])
            ->where('approver_id', $row['approver_rating_id'])
            ->where('status', 'Pending')
            ->first();
    }

    private function getConvertedValue($calibration, $rating)
    {
        $id_rating = $calibration->masterCalibration->first()->id_rating_group;
        $ratings = MasterRating::select('parameter', 'value')
            ->where('id_rating_group', $id_rating)
            ->get();
        $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
        return $ratingMap[$rating] ?? null;
    }

    private function addInvalidEmployee($row, $message)
    {
        $this->invalidEmployees[] = [
            'employee_id' => $row['employee_id'],
            'approver_id' => $row['approver_rating_id'],
            'your_rating' => $row['your_rating'],
            'message' => $message
        ];
        $this->invalidEmployeeIds[] = $row['employee_id'];
    }

    private function getQuota($ratingValue)
    {
        // Define quotas for each rating value (A, B, E)
        $quotas = [
            5 => 1,  // Rating A: Maximum 1 person
            4 => 1,  // Rating B: Maximum 1 person
            // 1 => 1   // Rating E: Maximum 1 person
        ];

        return $quotas[$ratingValue] ?? PHP_INT_MAX;
    }

    private function getMinimumQuota($ratingValue)
    {
        // Define minimum quotas for each rating value
        $minimumQuotas = [
            2 => 1  // Rating D: Minimum 1 person
        ];

        return $minimumQuotas[$ratingValue] ?? 0;
    }

    private function processValidRecords(Collection $collection)
    {
        foreach ($collection as $row) {
            // Skip invalid employee IDs
            if (in_array($row['employee_id'], $this->invalidEmployeeIds)) {
                continue;
            }

            // Get calibration data
            $calibration = Calibration::with(['masterCalibration'])
                ->where('employee_id', $row['employee_id'])
                ->where('approver_id', $row['approver_rating_id'])
                ->where('status', 'Pending')
                ->first();

            if (!$calibration) {
                continue;
            }

            // Get converted rating value
            $id_rating = $calibration->masterCalibration->first()->id_rating_group;
            $ratings = MasterRating::select('parameter', 'value')
                ->where('id_rating_group', $id_rating)
                ->get();

            $ratingMap = $ratings->pluck('value', 'parameter')->toArray();
            $convertedValue = $ratingMap[$row['your_rating']] ?? null;

            if (!$convertedValue) {
                continue;
            }

            try {
                // Start transaction
                DB::beginTransaction();

                // Update Calibration
                $updated = Calibration::where('approver_id', $row['approver_rating_id'])
                    ->where('employee_id', $row['employee_id'])
                    ->where('period', $this->period)
                    ->update([
                        'rating' => $convertedValue,
                        'status' => 'Approved',
                        'updated_by' => Auth::id()
                    ]);

                if ($updated) {
                    $nextApprover = $this->appService->processApproval(
                        $row['employee_id'], 
                        $row['approver_rating_id']
                    );
                    
                    if ($nextApprover) {
                        // Create new calibration for next approver
                        $createCalibration = new Calibration([
                            'id_calibration_group' => $calibration->id_calibration_group,
                            'appraisal_id' => $calibration->appraisal_id,
                            'employee_id' => $row['employee_id'],
                            'approver_id' => $nextApprover['next_approver_id'],
                            'period' => $this->period,
                            'created_by' => Auth::id()
                        ]);
                        $createCalibration->save();
                    } else {
                        // Final approval - update Appraisal
                        Appraisal::where('id', $calibration->appraisal_id)
                            ->update([
                                'rating' => $convertedValue,
                                'form_status' => 'Approved',
                                'updated_by' => Auth::id()
                            ]);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                
                // Add to invalid employees with error message
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_rating_id'],
                    'your_rating' => $row['your_rating'],
                    'message' => 'Error processing record: ' . $e->getMessage()
                ];
                
                $this->invalidEmployeeIds[] = $row['employee_id'];
                continue;
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
