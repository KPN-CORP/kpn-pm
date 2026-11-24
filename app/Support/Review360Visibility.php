<?php

namespace App\Support;

use App\Models\ApprovalLayerAppraisal;
use App\Models\EmployeeAppraisal;

/**
 * Kerahasiaan penilaian 360 (form dari peers & subordinate):
 *  - Tidak ada user yang boleh melihat penilaian 360 yang diberikan kepada DIRINYA.
 *  - Manager (approver layer 'manager' L1, L2, ...) tidak boleh melihat penilaian 360
 *    yang diberikan kepada TIMNYA. Self-review tim tetap boleh dilihat.
 *  - Penulis form 360 tetap boleh melihat form yang dia tulis sendiri.
 * Berlaku juga untuk admin (layar Reports > Appraisal dan export detail).
 * Self-review, review manager, dan summary/final rating tidak dibatasi.
 */
class Review360Visibility
{
    public const TYPES = ['peers', 'subordinate'];

    public static function is360(?string $contributorType): bool
    {
        return in_array(strtolower((string) $contributorType), self::TYPES, true);
    }

    /**
     * Dari $employeeIds, mana yang penilaian 360-nya TIDAK boleh dilihat viewer
     * (viewer sendiri, atau viewer adalah manager karyawan tsb).
     *
     * @return array<string, true>  employee_id => true
     */
    public static function restrictedTargets(?string $viewerEmpId, iterable $employeeIds): array
    {
        $viewer = (string) $viewerEmpId;
        $ids = collect($employeeIds)->filter()->map('strval')->unique()->values();

        if ($viewer === '' || $ids->isEmpty()) {
            return [];
        }

        $restricted = [];

        if ($ids->contains($viewer)) {
            $restricted[$viewer] = true;
        }

        foreach ($ids->chunk(1000) as $chunk) {
            $chunk = $chunk->all();

            // Semua layer manager (L1, L2, ...) dari Layer Setting appraisal.
            ApprovalLayerAppraisal::where('layer_type', 'manager')
                ->where('approver_id', $viewer)
                ->whereIn('employee_id', $chunk)
                ->pluck('employee_id')
                ->each(function ($id) use (&$restricted) {
                    $restricted[(string) $id] = true;
                });

            // L1/L2 versi AppraisalManagerResolver (Layer Setting, fallback
            // employees_pa), dihitung hanya untuk karyawan di $chunk.
            $rows = EmployeeAppraisal::select('employee_id', 'manager_l1_id', 'manager_l2_id')
                ->whereIn('employee_id', $chunk)
                ->get()
                ->keyBy(fn ($e) => (string) $e->employee_id);

            $layerL1 = AppraisalManagerResolver::layerL1Map($chunk);
            $l1Of = fn ($id) => $layerL1[(string) $id] ?? (string) ($rows[(string) $id]->manager_l1_id ?? '');

            $l1Ids = $rows->keys()->map($l1Of)->filter()->unique()->values()->all();
            $l1Layer = AppraisalManagerResolver::layerL1Map($l1Ids);
            $l1Pa = EmployeeAppraisal::whereIn('employee_id', $l1Ids)->pluck('manager_l1_id', 'employee_id');

            foreach ($rows as $id => $emp) {
                $l1 = $l1Of($id);
                $l2 = $l1 !== ''
                    ? ($l1Layer[$l1] ?? (string) ($l1Pa[$l1] ?? ''))
                    : '';
                $l2 = $l2 !== '' ? $l2 : (string) ($emp->manager_l2_id ?? '');

                if ($l1 === $viewer || $l2 === $viewer) {
                    $restricted[(string) $id] = true;
                }
            }
        }

        return $restricted;
    }

    public static function isRestrictedTarget(?string $viewerEmpId, ?string $employeeId): bool
    {
        return isset(self::restrictedTargets($viewerEmpId, [$employeeId])[(string) $employeeId]);
    }

    /**
     * Boleh viewer melihat satu form kontributor ini?
     */
    public static function canViewContribution(?string $viewerEmpId, $contributor): bool
    {
        if (! $contributor || ! self::is360($contributor->contributor_type ?? null)) {
            return true;
        }

        if ((string) $contributor->contributor_id === (string) $viewerEmpId) {
            return true; // form yang dia tulis sendiri
        }

        return ! self::isRestrictedTarget($viewerEmpId, $contributor->employee_id);
    }
}
