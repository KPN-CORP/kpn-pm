<?php

namespace App\Support;

use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\EmployeeAppraisal;
use App\Models\Flow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Assignment di flow "Propose 360" = daftar KARYAWAN YANG PERLU DI-360
 * (target penilaian), BUKAN siapa yang boleh membuka menu Propose 360.
 *
 * Akses menu/route Propose 360 diberikan ke user yang punya pekerjaan di sana:
 *  - dirinya sendiri termasuk assignment dan flow mengizinkan initiator 'self', atau
 *  - dia atasan L1 (Layer Setting appraisal) dari minimal satu karyawan yang termasuk assignment, atau
 *  - ada pengajuan Propose 360 periode ini yang sedang menunggu approval-nya.
 *
 * Aturan pencocokan (OR antar assignment, AND antar field, tidak peka huruf
 * besar/kecil) sama dengan daftar tim di Proposed360Controller::index.
 */
class Propose360Assignment
{
    public const MODULE = 'Propose 360';

    /** @var array<string, string>|null */
    private static ?array $ruleColumnsCache = null;

    /**
     * Decode kolom JSON flow. Data lama tersimpan double-encoded
     * ("\"[...]\""), data baru bisa sudah berupa array karena cast model.
     */
    public static function decodeJson($raw): array
    {
        for ($i = 0; $i < 3 && is_string($raw); $i++) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    public static function flow(): ?Flow
    {
        return Flow::forModule(self::MODULE);
    }

    public static function assignmentIds(?Flow $flow): array
    {
        if (! $flow) {
            return [];
        }

        return collect(self::decodeJson($flow->getAttributes()['assignments'] ?? null))
            ->map(fn ($v) => (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function allowsSelf(?Flow $flow): bool
    {
        if (! $flow) {
            return false;
        }

        return collect(self::decodeJson($flow->getAttributes()['initiator'] ?? null))
            ->contains(fn ($it) => strtolower((string) data_get($it, 'type')) === 'state'
                && strtolower((string) data_get($it, 'state_key')) === 'self');
    }

    /**
     * Rule-set per assignment (assignment yang di-soft-delete diabaikan).
     * Tiap rule-set: field => daftar nilai (string, trim).
     */
    public static function ruleSets(array $assignmentIds): array
    {
        if (empty($assignmentIds)) {
            return [];
        }

        $rows = DB::table('assignments')
            ->whereIn('id', $assignmentIds)
            ->whereNull('deleted_at')
            ->get(['id', 'restriction']);

        $ruleSets = [];

        foreach ($rows as $r) {
            $norm = [];
            foreach (self::decodeJson($r->restriction ?? '{}') as $key => $val) {
                if (is_array($val)) {
                    $norm[$key] = array_values(array_unique(array_map(
                        fn ($v) => (string) trim((string) $v),
                        $val
                    )));
                } elseif ($val !== null && $val !== '') {
                    $norm[$key] = [(string) trim((string) $val)];
                }
            }

            if (! empty($norm)) {
                $ruleSets[] = $norm;
            }
        }

        return $ruleSets;
    }

    /**
     * Kolom employees_pa yang dipakai rule assignment, per grup rule.
     * Key "a|b" = key rule yang dicoba berurutan => kolom pertama yang ada.
     *
     * @return array<string, string>
     */
    public static function ruleColumns(): array
    {
        if (self::$ruleColumnsCache !== null) {
            return self::$ruleColumnsCache;
        }

        $groups = [
            'designation_code|designation_name' => ['designation_code', 'designation_name'],
            'work_area_code' => ['work_area_code'],
            'group_company|group_company_code' => ['group_company', 'group_company_code'],
            'job_level' => ['job_level'],
        ];

        $existing = Schema::getColumnListing('employees_pa');

        $map = [];
        foreach ($groups as $keys => $candidateCols) {
            foreach ($candidateCols as $col) {
                if (in_array($col, $existing, true)) {
                    $map[$keys] = $col;
                    break;
                }
            }
        }

        return self::$ruleColumnsCache = $map;
    }

    /**
     * Apakah karyawan termasuk assignment (perlu di-360).
     * OR antar rule-set, AND antar grup di dalam satu rule-set.
     */
    public static function matches($emp, array $ruleSets, ?array $ruleColumns = null): bool
    {
        if (! $emp) {
            return false;
        }

        $ruleColumns ??= self::ruleColumns();
        $canon = static fn ($v) => mb_strtolower(rtrim((string) $v));

        foreach ($ruleSets as $rules) {
            $ok = true;

            foreach ($ruleColumns as $keys => $col) {
                $vals = [];
                foreach (explode('|', $keys) as $k) {
                    if (! empty($rules[$k]) && is_array($rules[$k])) {
                        $vals = array_values(array_filter(
                            array_map(static fn ($v) => trim((string) $v), $rules[$k]),
                            static fn ($v) => $v !== ''
                        ));
                        if (! empty($vals)) {
                            break;
                        }
                    }
                }
                if (empty($vals)) {
                    continue;
                }

                $value = $emp->{$col} ?? null;
                if ($value === null || ! in_array($canon($value), array_map($canon, $vals), true)) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah satu karyawan (by employee_id) perlu di-360 menurut flow aktif.
     */
    public static function employeeNeeds360(string $employeeId, ?Flow $flow = null): bool
    {
        $flow ??= self::flow();
        $ruleSets = self::ruleSets(self::assignmentIds($flow));

        if (empty($ruleSets)) {
            return false;
        }

        $emp = EmployeeAppraisal::select(array_values(array_unique(array_merge(
            ['employee_id'],
            array_values(self::ruleColumns())
        ))))->where('employee_id', $employeeId)->first();

        return self::matches($emp, $ruleSets);
    }

    /** @var array<int, array>|null rule-set flow aktif, cache per request */
    private static ?array $activeRuleSetsCache = null;

    /**
     * Karyawan TIDAK termasuk assignment 360 (mis. job level di luar range),
     * sehingga peers/subordinate di Layer Setting tidak perlu menilai dia.
     * Bila flow tidak punya assignment sama sekali => false (perilaku lama,
     * 360 tidak dibatasi), supaya tidak mematikan 360 untuk semua karyawan.
     */
    public static function excludedFrom360(string $employeeId): bool
    {
        return in_array($employeeId, self::excludedFrom360Ids([$employeeId]), true);
    }

    /**
     * Versi batch excludedFrom360: employee_id dari $employeeIds yang di luar
     * assignment 360 (satu query per 500 id).
     *
     * @return array<int, string>
     */
    public static function excludedFrom360Ids(iterable $employeeIds): array
    {
        self::$activeRuleSetsCache ??= self::ruleSets(self::assignmentIds(self::flow()));

        $ids = collect($employeeIds)->map(fn ($id) => (string) $id)->filter()->unique()->values();
        if (empty(self::$activeRuleSetsCache) || $ids->isEmpty()) {
            return [];
        }

        $columns = array_values(array_unique(array_merge(['employee_id'], array_values(self::ruleColumns()))));
        $excluded = [];

        foreach ($ids->chunk(500) as $chunk) {
            $rows = EmployeeAppraisal::withTrashed()->select($columns)->whereIn('employee_id', $chunk->all())->get();
            foreach ($rows as $emp) {
                if (! self::matches($emp, self::$activeRuleSetsCache)) {
                    $excluded[] = (string) $emp->employee_id;
                }
            }
        }

        return $excluded;
    }

    /**
     * Buang penilaian peers/subordinate PERIODE BERJALAN untuk karyawan di
     * luar assignment 360, supaya tidak ikut skor/summary. Data tidak
     * dihapus: kalau karyawan masuk assignment lagi, penilaiannya terhitung
     * lagi. Periode lalu tidak disentuh (assignment tidak terikat periode,
     * jadi menerapkannya ke riwayat akan mengubah report lama).
     *
     * @param  \Illuminate\Support\Collection  $contributors  AppraisalContributor (employee_id, contributor_type, period)
     */
    public static function dropIneligible360(\Illuminate\Support\Collection $contributors): \Illuminate\Support\Collection
    {
        $current = (string) app(\App\Services\AppService::class)->appraisalPeriod();

        $is360Now = fn ($c) => in_array($c->contributor_type, ['peers', 'subordinate'], true)
            && (string) $c->period === $current;

        $candidates = $contributors->filter($is360Now);
        if ($candidates->isEmpty()) {
            return $contributors;
        }

        $excluded = self::excludedFrom360Ids($candidates->pluck('employee_id'));
        if (empty($excluded)) {
            return $contributors;
        }

        return $contributors
            ->reject(fn ($c) => $is360Now($c) && in_array((string) $c->employee_id, $excluded, true))
            ->values();
    }

    /**
     * Boleh buka menu/route Propose 360? Lihat docblock class.
     */
    public static function canAccess(?string $employeeId, $period): bool
    {
        $employeeId = (string) $employeeId;
        if ($employeeId === '') {
            return false;
        }

        // Ada pengajuan yang menunggu approval user ini -> perlu menu untuk approve.
        $hasPending = ApprovalRequest::where('category', 'Proposed360')
            ->where('period', $period)
            ->where('current_approval_id', $employeeId)
            ->exists();
        if ($hasPending) {
            return true;
        }

        $flow = self::flow();
        $ruleSets = self::ruleSets(self::assignmentIds($flow));
        if (empty($ruleSets)) {
            return false;
        }

        $columns = array_values(array_unique(array_merge(
            ['employee_id', 'manager_l1_id'],
            array_values(self::ruleColumns())
        )));

        // Diri sendiri perlu di-360 dan boleh mengajukan sendiri.
        if (self::allowsSelf($flow)) {
            $self = EmployeeAppraisal::select($columns)->where('employee_id', $employeeId)->first();
            if (self::matches($self, $ruleSets)) {
                return true;
            }
        }

        // Atasan L1 dari karyawan yang perlu di-360. L1 mengikuti
        // AppraisalManagerResolver: approver layer 'manager' layer 1,
        // fallback employees_pa.manager_l1_id kalau karyawan tidak punya baris layer.
        $viaLayer = ApprovalLayerAppraisal::where('layer_type', 'manager')
            ->where('layer', 1)
            ->where('approver_id', $employeeId)
            ->pluck('employee_id')
            ->map('strval');

        $viaColumn = EmployeeAppraisal::where('manager_l1_id', $employeeId)
            ->pluck('employee_id')
            ->map('strval');

        if ($viaColumn->isNotEmpty()) {
            $hasLayer = ApprovalLayerAppraisal::where('layer_type', 'manager')
                ->where('layer', 1)
                ->whereIn('employee_id', $viaColumn)
                ->whereNotNull('approver_id')
                ->where('approver_id', '!=', '')
                ->pluck('employee_id')
                ->map('strval')
                ->all();
            $viaColumn = $viaColumn->reject(fn ($id) => in_array($id, $hasLayer, true));
        }

        $teamIds = $viaLayer->merge($viaColumn)->unique()->values();
        if ($teamIds->isEmpty()) {
            return false;
        }

        foreach ($teamIds->chunk(500) as $chunk) {
            $team = EmployeeAppraisal::select($columns)->whereIn('employee_id', $chunk->all())->get();
            foreach ($team as $emp) {
                if (self::matches($emp, $ruleSets)) {
                    return true;
                }
            }
        }

        return false;
    }
}
