<?php

namespace App\Support;

use App\Models\ApprovalLayerAppraisal;
use App\Models\EmployeeAppraisal;
use Illuminate\Support\Collection;

/**
 * Atasan L1/L2 untuk modul appraisal (dipakai Propose 360).
 *
 * Sumber utama adalah Layer Setting appraisal: approval_layer_appraisals
 * dengan layer_type = 'manager' dan layer = 1 (satu baris per karyawan).
 * Kolom employees_pa.manager_l1_id / manager_l2_id tidak pernah disinkron
 * ulang setelah baris dimuat, jadi sering tertinggal dari struktur yang
 * diatur admin di Layer Setting.
 *
 *  - L1 = approver layer 'manager' karyawan; kalau tidak ada,
 *         employees_pa.manager_l1_id.
 *  - L2 = L1 dari si L1 (aturan yang sama); kalau L1-nya tidak punya
 *         data, employees_pa.manager_l2_id karyawan.
 */
class AppraisalManagerResolver
{
    /**
     * Map employee_id => approver_id layer 'manager' layer 1.
     *
     * @param  iterable|null  $employeeIds  null = semua karyawan
     * @return array<string, string>
     */
    public static function layerL1Map(?iterable $employeeIds = null): array
    {
        $query = ApprovalLayerAppraisal::query()
            ->where('layer_type', 'manager')
            ->where('layer', 1)
            ->orderBy('id');

        if ($employeeIds !== null) {
            $ids = collect($employeeIds)->filter()->map('strval')->unique()->values();
            if ($ids->isEmpty()) {
                return [];
            }
            $query->whereIn('employee_id', $ids);
        }

        $map = [];
        foreach ($query->get(['employee_id', 'approver_id']) as $row) {
            if ($row->approver_id !== null && $row->approver_id !== '') {
                $map[(string) $row->employee_id] ??= (string) $row->approver_id;
            }
        }

        return $map;
    }

    /**
     * Timpa manager_l1_id / manager_l2_id pada koleksi EmployeeAppraisal
     * (keyed by employee_id) dengan hasil dari Layer Setting.
     * Koleksi harus berisi seluruh karyawan yang relevan supaya L2 bisa
     * diturunkan dari baris L1 tanpa query tambahan.
     */
    public static function applyToAll(Collection $employeesById): Collection
    {
        $layerL1 = self::layerL1Map();

        // Simpan nilai asli employees_pa sebelum ditimpa, untuk fallback L2.
        $paL1 = $employeesById->map(fn ($e) => (string) ($e->manager_l1_id ?? ''))->all();

        foreach ($employeesById as $empId => $emp) {
            $empId = (string) $empId;
            $l1 = $layerL1[$empId] ?? ($paL1[$empId] ?? '');
            $l2 = $l1 !== '' ? ($layerL1[$l1] ?? ($paL1[$l1] ?? '')) : '';

            $emp->manager_l1_id = $l1 !== '' ? $l1 : null;
            $emp->manager_l2_id = $l2 !== '' ? $l2 : ($emp->manager_l2_id ?: null);
        }

        return $employeesById;
    }

    /**
     * Versi satu karyawan, untuk jalur yang hanya butuh satu target
     * (submit Propose 360, resolve approver di ApprovalEngine).
     */
    public static function applyTo(?EmployeeAppraisal $emp): ?EmployeeAppraisal
    {
        if (! $emp) {
            return $emp;
        }

        $empId = (string) $emp->employee_id;
        $l1 = self::layerL1Map([$empId])[$empId] ?? (string) ($emp->manager_l1_id ?? '');

        $l2 = '';
        if ($l1 !== '') {
            $l2 = self::layerL1Map([$l1])[$l1]
                ?? (string) (EmployeeAppraisal::where('employee_id', $l1)->value('manager_l1_id') ?? '');
        }

        $emp->manager_l1_id = $l1 !== '' ? $l1 : null;
        $emp->manager_l2_id = $l2 !== '' ? $l2 : ($emp->manager_l2_id ?: null);

        return $emp;
    }
}
