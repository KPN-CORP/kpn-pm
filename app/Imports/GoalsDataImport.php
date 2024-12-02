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

    /**
     * Simpan data ke tabel setelah semua baris diproses.
     */
    public function saveToDatabase()
    {
        foreach ($this->employeesData as $employeeId => $data) {
            // Mulai transaksi
            DB::beginTransaction();

            try {
                $formId = Str::uuid();
                // Log data sebelum insert
                Log::info("Preparing to insert data for Employee ID: " . $employeeId, [
                    'form_data' => $data['form_data'],
                ]);

                // Hapus data lama jika ada
                DB::table('goals')
                    ->where('employee_id', $employeeId)
                    ->where('category', $data['category'])
                    ->where('period', $data['period'])
                    ->update(['deleted_at' => now()]);

                // Insert data baru
                DB::table('goals')->insert([
                    'id' => $formId,
                    'employee_id' => $employeeId,
                    'category' => $data['category'],
                    'form_data' => json_encode($data['form_data']), // Gabungkan semua KPI ke JSON
                    'form_status' => 'Approved',
                    'period' => $data['period'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update approval request
                DB::table('approval_requests')
                    ->where('employee_id', $employeeId)
                    ->where('category', $data['category'])
                    ->where('period', $data['period'])
                    ->update(['deleted_at' => now()]);

                // Dapatkan empId
                $empId = EmployeeAppraisal::where('employee_id', $employeeId)->pluck('id')->first();

                // Insert approval request
                $requestId = DB::table('approval_requests')->insertGetId([
                    'form_id' => $formId,  // Gunakan UUID yang sama
                    'category' => 'Goals',
                    'current_approval_id' => $data['current_approval_id'],
                    'employee_id' => $employeeId,
                    'status' => 'Approved',
                    'messages' => 'import by admin',
                    'period' => $data['period'],  // Periode 2024
                    'created_by' => $empId,  // ID admin yang melakukan import
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert approvals
                // DB::table('approvals')->insert([
                //     'request_id' => $requestId,
                //     'approver_id' => $data['current_approval_id'],
                //     'status' => 'Approved',
                //     'messages' => 'import by admin',
                //     'created_by' => $empId,  // ID admin yang melakukan import
                //     'created_at' => now(),
                // ]);

                // Jika semua sukses, commit transaksi
                DB::commit();

                // Increment success count
                $this->successCount++;
                Log::info("Data inserted for Employee ID: " . $employeeId);

            } catch (\Exception $e) {
                // Jika ada error, rollback transaksi
                DB::rollBack();

                // Log error
                Log::error("Error inserting data for Employee ID: " . $employeeId . ". Error: " . $e->getMessage());

                // Increment error count
                $this->errorCount++;
                $this->detailError[] = $employeeId;
            }
        }
    }

    /**
     * Validasi data Excel.
     */
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

    /**
     * Simpan transaksi import ke tabel log.
     */
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
