<?php

namespace App\Imports;

use App\Exports\InvalidApprovalAppraisalImport;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayer;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalLayerAppraisalBackup;
use App\Models\Employee;
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
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function collection(Collection $collection)
    {

        $headers = $collection->first()->keys();

        // Define the expected headers in the correct order
        $expectedHeaders = ['employee_id', 'approver_id', 'layer_type', 'layer'];
        $fileHeaders = $headers->values()->toArray(); // Get all headers from the file
        $filteredHeaders = array_values(array_intersect($expectedHeaders, $fileHeaders));

        $missingHeaders = array_diff($expectedHeaders, $fileHeaders);
        if (!empty($missingHeaders)) {
            // Handle missing headers case
            throw ValidationException::withMessages([
                'error' => 'Missing headers: '. implode(', ', $missingHeaders)
            ]);
        }

        // Check if the filtered headers match the expected headers in both content and order
        if ($filteredHeaders !== $expectedHeaders) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain employee_id, approver_id, layer_type, layer.'
            ]);
        }

        foreach ($collection as $row) {
            // Debug each row of data
            $rowArray = $row->toArray();

            $validate = Validator::make($rowArray, [
                'employee_id' => 'digits:11', // Ensure employee_id is exactly 11 digits
                'approver_id' => 'digits:11', // Ensure approver_id is exactly 11 digits
                // 'layer_type' => 'required|string|in:manager,peers,subordinate,calibrator', // Validate layer_type
                // 'layer' => 'required|integer|min:1|max:3', // Validate layer
            ]);

            $contributor = AppraisalContributor::where('employee_id', $row['employee_id'])
                            ->where('status', 'Approved')
                            ->where('period', $this->period)
                            ->get();

            if ($validate->fails()) {
                // Collect the invalid rows and the error messages
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'employee_id or approver_id have invalid format ID.', // Get the first error message
                    // 'message' => $validator->errors()->first(), // Get the first error message
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Store invalid employee_ids for reporting
            }

            if ($contributor->count() > 0) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Layer update failed: Employee is already in the calibration process.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }

            if (empty($row['approver_id'])) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Approver ID is missing.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
        
            if (empty($row['layer_type'])) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Layer type is missing.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
        
            $allowedLayerTypes = ['manager', 'peers', 'subordinate', 'calibrator'];
            $employeeLayers = [];
        
            if ($row['layer_type'] && !in_array($row['layer_type'], $allowedLayerTypes)) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Layer type is invalid.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
        
            if (empty($row['layer'])) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Layer is missing.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }

            // Additional validation for layer_type = 'manager'
            if (in_array($row['layer_type'], ['manager']) && $row['layer'] > 1) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Manager cannot be more than 1 layer.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }

            // Additional validation for layer_type = 'peers' or 'subordinate'
            if (in_array($row['layer_type'], ['peers', 'subordinate']) && $row['layer'] > 3) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Layer cannot be greater than 3.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
        
            $approver = Employee::where('employee_id', $row['approver_id'])->first();
            if (!$approver) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Approver ID does not exist.'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            }
        
            $combination = $row['employee_id'] . '-' . $row['layer_type'] . '-' . $row['layer'];
            if (in_array($combination, $this->usedCombinations)) {
                $this->invalidEmployees[] = [
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'message' => 'Conflict detected for this employee: Duplicate or conflicting entry'
                ];
                $this->invalidEmployeeIds[] = $row['employee_id']; // Track invalid employee_id
            } else {
                $this->usedCombinations[] = $combination; // Mark this combination as used
            }
            
        }
        
        // Remove duplicates from invalid employee IDs list
        $this->invalidEmployeeIds = array_unique($this->invalidEmployeeIds);

        foreach ($collection as $row) {
            $this->employeeIds[] = $row['employee_id'];
        }

        $this->employeeIds = array_unique($this->employeeIds);

        if (!in_array($row['employee_id'], $this->invalidEmployeeIds)) {

            // Backup data sebelum menghapus
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
            foreach ($collection as $row) {


            // Skip rows with invalid employee_id
                // Hapus data lama
            
                ApprovalLayerAppraisal::create([
                    'employee_id' => $row['employee_id'],
                    'approver_id' => $row['approver_id'],
                    'layer_type' => $row['layer_type'],
                    'layer' => $row['layer'],
                    'created_by' => $this->userId,
                ]);
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
