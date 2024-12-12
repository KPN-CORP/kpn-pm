<?php
namespace App\Imports;

use App\Models\EmployeeAppraisal;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class GoalsDataImport implements ToModel, WithValidation, WithHeadingRow
{
    public $successCount = 0; // Hitungan data berhasil
    public $errorCount = 0;   // Hitungan data gagal
    public $filePath;
    public $employeesData = []; // Untuk menyimpan semua data berdasarkan employee_id
    public $detailError = [];

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Mapping data Excel ke model.
     */
    public function model(array $row)
    {
        Log::info("Processing row: ", $row);

        try {
            $employeeId = $row['employee_id'];

            // Simpan data KPI ke array berdasarkan employee_id
            if (!isset($this->employeesData[$employeeId])) {
                $this->employeesData[$employeeId] = [
                    'category' => $row['category'],
                    'form_data' => [],
                    'current_approval_id' => $row['current_approver_id'],  // Menyimpan langsung current_approval_id
                    'period' => $row['period'],  // Menyimpan langsung period
                ];
            }

            // Tambahkan data KPI ke form_data
            $this->employeesData[$employeeId]['form_data'][] = [
                'kpi' => $row['kpi'],
                'target' => $row['target'],
                'uom' => $row['uom'],
                'weightage' => $row['weightage'],
                'type' => $row['type'],
                'custom_uom' => null,
            ];
        } catch (\Exception $e) {
            Log::error("Error processing row: " . $e->getMessage());
            $this->errorCount++;
            $this->detailError[] = $row['employee_id'];
        }
    }

    public function saveToDatabase()
    {
        foreach ($this->employeesData as $employeeId => $data) {

            DB::beginTransaction();

            try {
                $existsInAppraisals = DB::table('appraisals')
                    ->where('employee_id', $employeeId)
                    ->exists();

                if ($existsInAppraisals) {
                    $message = "Employee ID: $employeeId already has appraisal data.";
                    Log::info($message);
                    
                    $this->detailError[] = [
                        'employee_id' => $employeeId,
                        'message' => $message,
                    ];

                    $this->errorCount++;
                    DB::rollBack();
                    continue;
                }

                $formId = Str::uuid();

                Log::info("Preparing to insert data for Employee ID: " . $employeeId, [
                    'form_data' => $data['form_data'],
                ]);

                Log::info("Starting transaction for Employee ID: " . $employeeId);

                Log::info("Deleting old data for Employee ID: " . $employeeId);
                DB::table('goals')
                    ->where('employee_id', $employeeId)
                    ->where('category', $data['category'])
                    ->where('period', $data['period'])
                    ->update(['deleted_at' => now()]);
                Log::info("Old data deleted for Employee ID: " . $employeeId);

                Log::info("Data for Employee ID: " . $employeeId, $data);
                DB::table('goals')->insert([
                    'id' => $formId,
                    'employee_id' => $employeeId,
                    'category' => $data['category'],
                    'form_data' => json_encode($data['form_data']),
                    'form_status' => 'Approved',
                    'period' => $data['period'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info("Old goals updated for Employee ID: " . $employeeId);

                DB::table('approval_requests')
                    ->where('employee_id', $employeeId)
                    ->where('category', $data['category'])
                    ->where('period', $data['period'])
                    ->update(['deleted_at' => now()]);

                $empId = EmployeeAppraisal::where('employee_id', $employeeId)->pluck('id')->first();

                if ($empId) {
                    Log::info("EmployeeAppraisal ID found for Employee ID: " . $employeeId . ". EmpId: " . $empId);
                } else {
                    Log::error("No EmployeeAppraisal record found for Employee ID: " . $employeeId);
                }

                $requestId = DB::table('approval_requests')->insertGetId([
                    'form_id' => $formId,  // Gunakan UUID yang sama
                    'category' => 'Goals',
                    'current_approval_id' => $data['current_approval_id'],
                    'employee_id' => $employeeId,
                    'status' => 'Approved',
                    'messages' => 'import by admin',
                    'period' => $data['period'],
                    'created_by' => $empId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                $this->successCount++;
                Log::info("Data inserted for Employee ID: " . $employeeId);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error("Error inserting data for Employee ID: " . $employeeId . ". Error: " . $e->getMessage());

                $this->errorCount++;
                $this->detailError[] = [
                    'employee_id' => $employeeId,
                    'message' => "Error during import: " . $e->getMessage(),
                ];
            }
        }
    }

    public function rules(): array
    {
        Log::info("Validating Excel data 2...");
        return [
            'employee_id' => 'required|string',
            'employee_name' => 'required|string',
            'category' => 'required|string',
            'kpi' => 'required|string',
            'target' => 'required|numeric',
            'uom' => 'required|string',
            'weightage' => 'required|numeric',
            'type' => 'required|string',
        ];
    }

    public function saveTransaction()
    {
        $filePathWithoutPublic = str_replace('public/', '', $this->filePath);
        DB::table('goals_import_transactions')->insert([
            'success' => $this->successCount,
            'error' => $this->errorCount,
            'detail_error' => $this->detailError ? json_encode($this->detailError) : null,
            'file_uploads' => $filePathWithoutPublic,
            'submit_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
