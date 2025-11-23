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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalLayerAppraisalImport implements ToCollection, WithHeadingRow
{
    /**
     * ID user yang meng-upload
     *
     * @var int|string
     */
    protected $userId;

    /**
     * Periode appraisal (YYYY)
     *
     * @var string
     */
    protected $period;

    /**
     * List error per employee untuk diexport
     *
     * @var array<int, array<string, mixed>>
     */
    protected $invalidEmployees = [];

    /**
     * List employee_id yang invalid (tidak akan di-update)
     *
     * @var array<int, string>
     */
    protected $invalidEmployeeIds = [];

    /**
     * List employee_id unik dari file
     *
     * @var array<int, string>
     */
    protected $employeeIds = [];

    /**
     * Header yang diharapkan dari file Excel.
     *
     * @var array<int, string>
     */
    protected $expectedHeaders = [
        'employee_id', 'employee_name', 'manager_id_1', 'manager_name_1',
        'peers_id_1', 'peers_name_1', 'peers_id_2', 'peers_name_2', 'peers_id_3', 'peers_name_3',
        'subordinate_id_1', 'subordinate_name_1', 'subordinate_id_2', 'subordinate_name_2', 'subordinate_id_3', 'subordinate_name_3',
        'calibrator_id_1', 'calibrator_name_1', 'calibrator_id_2', 'calibrator_name_2', 'calibrator_id_3', 'calibrator_name_3',
        'calibrator_id_4', 'calibrator_name_4', 'calibrator_id_5', 'calibrator_name_5', 'calibrator_id_6', 'calibrator_name_6',
        'calibrator_id_7', 'calibrator_name_7', 'calibrator_id_8', 'calibrator_name_8', 'calibrator_id_9', 'calibrator_name_9',
        'calibrator_id_10', 'calibrator_name_10',
    ];

    public function __construct($userId, $period)
    {
        $this->userId = $userId;
        $this->period = $period;
    }

    /**
     * Entry point import
     */
    public function collection(Collection $collection)
    {
        // 1. Validasi header
        $this->validateHeaders($collection);

        // 2. Ambil semua employee_id unik dari file
        $this->employeeIds = $collection
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($this->employeeIds)) {
            return;
        }

        // 3. Validasi per-row (tanpa mengubah DB)
        $this->validateRows($collection);

        // Employee yang valid (boleh diupdate DB)
        $validEmployeeIds = array_values(array_diff(
            $this->employeeIds,
            $this->invalidEmployeeIds
        ));

        if (empty($validEmployeeIds)) {
            // Semua invalid, cukup simpan error untuk diexport
            return;
        }

        // 4. Proses update DB untuk employee yang valid
        $this->processImport($collection, $validEmployeeIds);
    }

    /**
     * Export error ke Excel
     */
    public function exportInvalidEmployees()
    {
        if (!empty($this->invalidEmployees)) {
            return Excel::download(
                new InvalidApprovalAppraisalImport($this->invalidEmployees),
                'errors_layer_import.xlsx'
            );
        }

        return null;
    }

    /**
     * Getter error list
     */
    public function getInvalidEmployees()
    {
        return $this->invalidEmployees;
    }

    /**
     * Validasi header file Excel.
     */
    protected function validateHeaders(Collection $collection): void
    {
        $headers = $collection->first()->keys();
        $fileHeaders = $headers->values()->toArray();

        $filteredHeaders = array_values(array_intersect($this->expectedHeaders, $fileHeaders));
        $missingHeaders = array_diff($this->expectedHeaders, $fileHeaders);

        if (!empty($missingHeaders)) {
            throw ValidationException::withMessages([
                'error' => 'Missing headers: ' . implode(', ', $missingHeaders),
            ]);
        }

        if ($filteredHeaders !== $this->expectedHeaders) {
            throw ValidationException::withMessages([
                'error' => 'Invalid excel format. The header must contain the following columns in the specified order: '
                    . implode(', ', $this->expectedHeaders),
            ]);
        }
    }

    /**
     * Validasi logika per-employee & per-approver.
     * Hanya mengisi $invalidEmployees dan $invalidEmployeeIds, belum menyentuh DB.
     */
    protected function validateRows(Collection $collection): void
    {
        $this->invalidEmployees = [];
        $this->invalidEmployeeIds = [];

        foreach ($collection as $row) {
            $employeeId = trim((string)($row['employee_id'] ?? ''));

            if ($employeeId === '') {
                // Kalau mau dipaksa error: bisa tambahkan addError di sini
                continue;
            }

            $hasError = false;

            // 1. Tidak boleh diubah jika sudah Approved Calibration
            $alreadyCalibrated = Calibration::where('employee_id', $employeeId)
                ->where('period', $this->period)
                ->where('status', 'Approved')
                ->exists();

            if ($alreadyCalibrated) {
                $this->addError(
                    $employeeId,
                    '',
                    '',
                    '',
                    "Cannot change layer. Employee ID {$employeeId} is already under calibration process."
                );
                $hasError = true;
            }

            // 2. Validasi keberadaan employee (optional rule dari kode lama)
            if (!$hasError && !empty($employeeId)) {
                $employeeExists = Employee::where('employee_id', $employeeId)->exists();

                // Mengikuti pola lama: kalau tidak exist dan length != 11 → error
                if (!$employeeExists && strlen($employeeId) !== 11) {
                    $this->addError(
                        $employeeId,
                        '',
                        '',
                        '',
                        "Employee ID {$employeeId} does not exist."
                    );
                    $hasError = true;
                }
            }

            if ($hasError) {
                $this->invalidEmployeeIds[] = $employeeId;
                continue;
            }

            // 3. Validasi per kolom approver
            $hasFieldError = false;
            $hasCalibratorLayer1 = false;

            foreach ($row as $header => $value) {
                if (!preg_match('/^(manager|peers|subordinate|calibrator)_id_(\d+)$/', $header, $matches)) {
                    continue;
                }

                $layerType  = $matches[1];           // manager|peers|subordinate|calibrator
                $layer      = (int)$matches[2];      // 1..n
                $approverId = trim((string)$value);

                if ($layerType === 'calibrator' && $layer === 1 && $approverId !== '') {
                    $hasCalibratorLayer1 = true;
                }

                // Manager layer 1 wajib diisi
                if ($layerType === 'manager' && $layer === 1 && $approverId === '') {
                    $this->addError(
                        $employeeId,
                        $approverId,
                        $layerType,
                        $layer,
                        "Manager ID is mandatory for layer {$layer}."
                    );
                    $hasFieldError = true;
                    continue;
                }

                // peers/subordinate boleh kosong → skip (tidak error)
                if ($approverId === '') {
                    continue;
                }

                // Validasi format approver (11 digit numeric)
                if (!ctype_digit($approverId) || strlen($approverId) !== 11) {
                    $this->addError(
                        $employeeId,
                        $approverId,
                        $layerType,
                        $layer,
                        "Invalid approver_id must be 11 digits for {$header}."
                    );
                    $hasFieldError = true;
                    continue;
                }

                // Pastikan approver ada di tabel employee
                $approverExists = Employee::where('employee_id', $approverId)->exists();
                if (!$approverExists) {
                    $this->addError(
                        $employeeId,
                        $approverId,
                        $layerType,
                        $layer,
                        "Approver ID {$approverId} does not exist."
                    );
                    $hasFieldError = true;
                    continue;
                }
            }

            // Wajib punya minimal 1 calibrator layer 1
            if (!$hasCalibratorLayer1) {
                $this->addError(
                    $employeeId,
                    '',
                    'calibrator',
                    1,
                    "add at least one calibrator in layer 1."
                );
                $hasFieldError = true;
            }

            if ($hasFieldError) {
                $this->invalidEmployeeIds[] = $employeeId;
            }
        }

        $this->invalidEmployeeIds = array_values(array_unique($this->invalidEmployeeIds));
    }

    /**
     * Proses perubahan DB:
     * - Overwrite manager & calibrator untuk employee valid.
     * - Overwrite peers/subordinate hanya jika ada value di file.
     */
    protected function processImport(Collection $collection, array $validEmployeeIds): void
    {
        DB::transaction(function () use ($collection, $validEmployeeIds) {

            // 1. Backup & delete semua manager + calibrator untuk employee valid
            $managerCalibrator = ApprovalLayerAppraisal::whereIn('employee_id', $validEmployeeIds)
                ->whereIn('layer_type', ['manager', 'calibrator'])
                ->get();

            foreach ($managerCalibrator as $layer) {
                $this->backupLayer($layer);
            }

            if ($managerCalibrator->isNotEmpty()) {
                ApprovalLayerAppraisal::whereIn('id', $managerCalibrator->pluck('id'))->delete();
            }

            // Flag untuk menghindari delete peers/subordinate berulang
            $clearedPeersSubs = [];

            // 2. Loop per row untuk insert data baru
            foreach ($collection as $row) {
                $employeeId = trim((string)($row['employee_id'] ?? ''));

                if (
                    $employeeId === '' ||
                    !in_array($employeeId, $validEmployeeIds, true)
                ) {
                    continue; // skip employee invalid atau kosong
                }

                foreach ($row as $header => $value) {
                    if (!preg_match('/^(manager|peers|subordinate|calibrator)_id_(\d+)$/', $header, $matches)) {
                        continue;
                    }

                    $layerType  = $matches[1];
                    $layer      = (int)$matches[2];
                    $approverId = trim((string)$value);

                    // kosong → tidak insert apa-apa
                    if ($approverId === '') {
                        continue;
                    }

                    // peers/subordinate hanya dihapus kalau file ada datanya
                    if (in_array($layerType, ['peers', 'subordinate'], true)) {
                        $key = $employeeId . '|' . $layerType . '|' . $layer;

                        if (!isset($clearedPeersSubs[$key])) {
                            $existing = ApprovalLayerAppraisal::where('employee_id', $employeeId)
                                ->where('layer_type', $layerType)
                                ->where('layer', $layer)
                                ->get();

                            foreach ($existing as $exLayer) {
                                $this->backupLayer($exLayer);
                            }

                            if ($existing->isNotEmpty()) {
                                ApprovalLayerAppraisal::whereIn('id', $existing->pluck('id'))->delete();
                            }

                            $clearedPeersSubs[$key] = true;
                        }
                    }

                    // Update calibration pending untuk calibrator layer 1
                    if ($layerType === 'calibrator' && $layer === 1) {
                        $pendingCal = Calibration::where('employee_id', $employeeId)
                            ->where('period', $this->period)
                            ->where('status', 'Pending')
                            ->first();

                        if ($pendingCal && $pendingCal->approver_id !== $approverId) {
                            $pendingCal->approver_id = $approverId;
                            $pendingCal->updated_by  = $this->userId;
                            $pendingCal->save();
                        }
                    }

                    // Create record baru
                    ApprovalLayerAppraisal::create([
                        'employee_id' => $employeeId,
                        'approver_id' => $approverId,
                        'layer_type'  => $layerType,
                        'layer'       => $layer,
                        'created_by'  => $this->userId,
                    ]);
                }
            }
        });
    }

    /**
     * Utility untuk menambah error ke list.
     */
    protected function addError(
        string $employeeId,
        ?string $approverId,
        ?string $layerType,
        $layer,
        string $message
    ): void {
        $this->invalidEmployees[] = [
            'employee_id' => $employeeId,
            'approver_id' => $approverId ?? '',
            'layer_type'  => $layerType ?? '',
            'layer'       => $layer ?? '',
            'message'     => $message,
        ];
    }

    /**
     * Backup satu baris ApprovalLayerAppraisal ke tabel backup.
     */
    protected function backupLayer(ApprovalLayerAppraisal $layer): void
    {
        ApprovalLayerAppraisalBackup::create([
            'employee_id' => $layer->employee_id,
            'approver_id' => $layer->approver_id,
            'layer_type'  => $layer->layer_type,
            'layer'       => $layer->layer,
            'created_by'  => $layer->created_by ?? 0,
            'created_at'  => $layer->created_at,
        ]);
    }
}
