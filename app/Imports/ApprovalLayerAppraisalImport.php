<?php
namespace App\Imports;

use App\Exports\InvalidApprovalAppraisalImport;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayer;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalLayerAppraisalBackup;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalLayerAppraisalImport implements ToCollection, WithHeadingRow
{
    protected $userId;
    protected $period;
    protected $invalidEmployees = [];
    protected $usedCombinations = [];
    protected $invalidEmployeeIds = [];
    protected $employeeIds = [];

    public function __construct($userId, $period)
    {
        $this->userId = $userId;
        $this->period = $period;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $headers = $collection->first()->keys();

        // Define the expected headers in the correct order
        $expectedHeaders = [
            'employee_id', 'employee_name', 'manager_id_1', 'manager_name_1',
            'peers_id_1', 'peers_name_1', 'peers_id_2', 'peers_name_2', 'peers_id_3', 'peers_name_3',
            'subordinate_id_1', 'subordinate_name_1', 'subordinate_id_2', 'subordinate_name_2', 'subordinate_id_3', 'subordinate_name_3',
            'calibrator_id_1', 'calibrator_name_1', 'calibrator_id_2', 'calibrator_name_2', 'calibrator_id_3', 'calibrator_name_3',
            'calibrator_id_4', 'calibrator_name_4', 'calibrator_id_5', 'calibrator_name_5', 'calibrator_id_6', 'calibrator_name_6',
            'calibrator_id_7', 'calibrator_name_7', 'calibrator_id_8', 'calibrator_name_8', 'calibrator_id_9', 'calibrator_name_9',
            'calibrator_id_10', 'calibrator_name_10'
        ];

        $fileHeaders = $headers->values()->toArray(); // Get all headers from the file
        $filteredHeaders = array_values(array_intersect($expectedHeaders, $fileHeaders));
        $missingHeaders = array_diff($expectedHeaders, $fileHeaders);

        if (!empty($missingHeaders)) {
            // Handle missing headers case
            throw ValidationException::withMessages([
                'error' => 'Missing headers: ' . implode(', ', $missingHeaders)
            ]);
        }

        // Check if the filtered headers match the expected headers in both content and order
        if ($filteredHeaders !== $expectedHeaders) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain the following columns in the specified order: ' .
                    implode(', ', $expectedHeaders)
            ]);
        }

        // Remove duplicates from invalid employee IDs list
        $this->invalidEmployeeIds = array_unique($this->invalidEmployeeIds);

        foreach ($collection as $row) {
            $this->employeeIds[] = $row['employee_id'];
        }

        $this->employeeIds = array_unique($this->employeeIds);

        if (!in_array($row['employee_id'], $this->invalidEmployeeIds)) {

            foreach ($collection as $row) {
                // Skip rows with invalid employee_id
                if (in_array($row['employee_id'], $this->invalidEmployeeIds)) {
                    continue;
                }

                if (Calibration::where('employee_id', $row['employee_id'])
                    ->where('period', $this->period)
                    ->where('status', 'Approved')
                    ->exists()) {
                    $this->invalidEmployees[] = [
                        'employee_id' => $row['employee_id'],
                        'approver_id' => '',
                        'layer_type' => '',
                        'layer' => '',
                        'message' => "Cannot change layer. Employee ID {$row['employee_id']} is already under calibration process.",
                    ];
                    $this->invalidEmployeeIds[] = $row['employee_id'];
                    continue; // Skip further processing for this row
                }
            
                // Backup data before deleting
                $appraisalLayersToDelete = ApprovalLayerAppraisal::whereIn('employee_id', $this->employeeIds)->get();
                foreach ($appraisalLayersToDelete as $layer) {
                    ApprovalLayerAppraisalBackup::create([
                        'employee_id' => $layer->employee_id,
                        'approver_id' => $layer->approver_id,
                        'layer_type' => $layer->layer_type,
                        'layer' => $layer->layer,
                        'created_by' => $layer->created_by ? $layer->created_by : 0,
                        'created_at' => $layer->created_at,
                    ]);
                }
            
                ApprovalLayerAppraisal::whereIn('employee_id', $this->employeeIds)->delete();
            
                // Second pass to process and import data
                foreach ($row as $header => $value) {
                    // Match headers that end with '_id' but exclude 'employee_id'
                    if (preg_match('/^(manager|peers|subordinate|calibrator)_id_(\d+)$/', $header, $matches)) {
                        $layerType = $matches[1]; // e.g., 'manager', 'peers', etc.
                        $layer = (int)$matches[2]; // e.g., 1, 2, 3, etc.
                        $approverId = $value; // The value of the header (e.g., manager_id_1)
            
                        if(!empty($row['employee_id'])){
                            // Validate employee_id existence in Employee model
                            $employeeExists = Employee::where('employee_id', $row['employee_id'])->exists();
                            if (!$employeeExists && strlen($row['employee_id']) !== 11) {
                                $this->invalidEmployees[] = [
                                    'employee_id' => $row['employee_id'],
                                    'approver_id' => $approverId,
                                    'layer_type' => $layerType,
                                    'layer' => $layer,
                                    'message' => "Employee ID {$row['employee_id']} does not exist.",
                                ];
                                $this->invalidEmployeeIds[] = $row['employee_id'];
                                continue; // Skip further processing for this row
                            }
                        }

                        if ($layerType === 'manager' && empty($approverId)) {
                            $this->invalidEmployees[] = [
                                'employee_id' => $row['employee_id'],
                                'approver_id' => $approverId,
                                'layer_type' => $layerType,
                                'layer' => $layer,
                                'message' => "Manager ID is mandatory for layer {$layer}.",
                            ];
                            $this->invalidEmployeeIds[] = $row['employee_id'];
                            continue;
                        }

                        if ($layerType === 'calibrator' && $layer === 1 && empty($approverId)) {
                            $this->invalidEmployees[] = [
                                'employee_id' => $row['employee_id'],
                                'approver_id' => $approverId,
                                'layer_type' => $layerType,
                                'layer' => $layer,
                                'message' => "add at least one calibrator in layer 1.",
                            ];
                            $this->invalidEmployeeIds[] = $row['employee_id'];
                            continue;
                        }

                        // Skip validation for peers and subordinate if approver_id is empty
                        if (($layerType === 'peers' || $layerType === 'subordinate') && empty($approverId)) {
                            continue; // Skip further processing for this row
                        }

                        // Validate approver_id existence in Employee model (only if employee_id exists)
                        $approverExists = Employee::where('employee_id', $approverId)->exists();
                        if (!empty($approverId)) {
                            // Validate approver_id format (ensure it is exactly 11 digits)
                            if (!is_numeric($approverId) || strlen($approverId) !== 11) {
                                $this->invalidEmployees[] = [
                                    'employee_id' => $row['employee_id'],
                                    'approver_id' => $approverId,
                                    'layer_type' => $layerType,
                                    'layer' => $layer,
                                    'message' => "Invalid approver_id must be 11 digits for {$header}.",
                                ];
                                $this->invalidEmployeeIds[] = $row['employee_id'];
                                continue; // Skip further processing for this row
                            }
                        
                            // Validate approver_id existence in Employee model
                            $approverExists = Employee::where('employee_id', $approverId)->exists();
                            if (!$approverExists) {
                                $this->invalidEmployees[] = [
                                    'employee_id' => $row['employee_id'],
                                    'approver_id' => $approverId,
                                    'layer_type' => $layerType,
                                    'layer' => $layer,
                                    'message' => "Approver ID {$approverId} does not exist.",
                                ];
                                $this->invalidEmployeeIds[] = $row['employee_id'];
                                continue; // Skip further processing for this row
                            }
                        } else {
                            // Skip empty approver_id values
                            continue;
                        }

                        if ($layerType === 'calibrator' && $layer === 1 && !empty($approverId)) {
                            $checkCalibration = Calibration::where('employee_id', $row['employee_id'])
                                ->where('period', $this->period)
                                ->where('status', 'Pending')
                                ->first();

                            if ($checkCalibration && $checkCalibration->approver_id != $approverId) {
                                $checkCalibration->approver_id = $approverId; // Assign calibrator_id_1 as the new approver ID
                                $checkCalibration->updated_by = $this->userId; // Set the current authenticated user as `updated_by`
                                $checkCalibration->save(); // Save changes to the database
                            }
                        }

                        // Create a new record in ApprovalLayerAppraisal
                        ApprovalLayerAppraisal::create([
                            'employee_id' => $row['employee_id'],
                            'approver_id' => $approverId,
                            'layer_type' => $layerType,
                            'layer' => $layer,
                            'created_by' => $this->userId,
                        ]);
                    }
                }
            }
        }
    }

    public function exportInvalidEmployees()
    {
        if (!empty($this->invalidEmployees)) {
            // Export the invalid employees with error messages to an Excel file
            return Excel::download(new InvalidApprovalAppraisalImport($this->invalidEmployees), 'errors_layer_import.xlsx');
        }
        return null;
    }

    // Return the list of invalid employees with error messages
    public function getInvalidEmployees()
    {
        return $this->invalidEmployees;
    }
}