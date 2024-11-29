<?php
namespace App\Imports;

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
            try {
                // Log data sebelum insert
                Log::info("Preparing to insert data for Employee ID: " . $employeeId, [
                    'form_data' => $data['form_data'],
                ]);

                // Hapus data lama jika ada
                DB::table('goals')->where('employee_id', $employeeId)->delete();

                // Insert data baru
                DB::table('goals')->insert([
                    'id' => Str::uuid(),
                    'employee_id' => $employeeId,
                    'category' => $data['category'],
                    'form_data' => json_encode($data['form_data']), // Gabungkan semua KPI ke JSON
                    'form_status' => 'Approved',
                    'period' => now()->year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->successCount++;
                Log::info("Data inserted for Employee ID: " . $employeeId);
            } catch (\Exception $e) {
                Log::error("Error inserting data for Employee ID: " . $employeeId . ". Error: " . $e->getMessage());
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
