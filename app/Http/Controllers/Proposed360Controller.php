<?php

namespace App\Http\Controllers;

use App\Models\ApprovalFlow;
use App\Models\ApprovalLog;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Flow;
use App\Models\Proposed360;
use App\Services\ApprovalEngine;
use App\Services\AppService;
use App\Services\Proposed360Service;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class Proposed360Controller extends Controller
{
    protected $appService;
    protected $period;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->period = $this->appService->appraisalPeriod();
    }

    public function index(Request $request)
    {
        $period    = $this->period;
        $authEmpId = (string) Auth::user()->employee_id;

        /* ===========================================
        * 1) FLOW & SELF
        * =========================================== */
        $flowRow       = Flow::where('module_transaction', 'Propose 360')->first();
        $assignmentIds = $this->parseAssignmentIds($flowRow);
        $teamRuleSets  = $this->loadTeamAssignments($assignmentIds);
        $selfEnabled   = $this->flowAllowsSelf($flowRow);

        $selfRow = EmployeeAppraisal::select(
                'id','employee_id','fullname','designation_name',
                'manager_l1_id','manager_l2_id'
            )
            ->with(['appraisalLayer' => fn($q) => $q->whereIn('layer_type',['peers','subordinate'])])
            ->where('employee_id', $authEmpId)
            ->first();

        $self = collect();
        $selfPeers = collect();

        if ($selfRow) {
            $selfRow->peers       = $selfRow->appraisalLayer->where('layer_type','peers')->values();
            $selfPeers            = $this->buildPeerCandidatesFor($selfRow->employee_id);
            $self                 = collect([$selfRow]);
        }

        /* ===========================================
        * 2) TEAM (Manager L1)
        * =========================================== */
        $datas = collect();
        $subordinates = collect();

        if ($selfRow && !empty($teamRuleSets)) {

            $datas = EmployeeAppraisal::select(
                    'id','employee_id','fullname','designation_name','manager_l1_id','manager_l2_id'
                )
                ->with(['appraisalLayer'=>fn($q)=>$q->whereIn('layer_type',['peers','subordinate'])])
                ->where('manager_l1_id', $authEmpId)
                ->where(function ($outer) use ($teamRuleSets, $authEmpId) {
                    foreach ($teamRuleSets as $rules) {
                        $outer->orWhere(function ($q) use ($rules, $authEmpId) {
                            $this->applySingleRuleToQuery($q, $rules, $authEmpId);
                        });
                    }
                })
                ->get();

            $subordinates = EmployeeAppraisal::select(
                    'id','employee_id','fullname','designation_name','manager_l1_id','manager_l2_id'
                )
                ->with(['appraisalLayer'=>fn($q)=>$q->whereIn('layer_type',['peers','subordinate'])])
                ->where('manager_l2_id',$authEmpId)
                ->orWhere('manager_l1_id',$authEmpId)
                ->get();
        }

        /* ===========================================
        * 3) TEAM SUBORDINATES
        * =========================================== */
        $teamIds = $datas->pluck('employee_id')->all();

        $childrenRaw = EmployeeAppraisal::select(
                'id','employee_id','fullname','designation_name',
                'manager_l1_id','manager_l2_id'
            )
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('manager_l1_id', $teamIds)
                ->orWhereIn('manager_l2_id', $teamIds);
            })
            ->get();

        $children = collect();
        foreach ($teamIds as $tid) {
            $children[$tid] = $childrenRaw
                ->where('manager_l1_id', $tid)
                ->merge($childrenRaw->where('manager_l2_id', $tid))
                ->unique('employee_id')
                ->values();
        }

        // isi subordinate & peers untuk TEAM
        $datas->transform(function ($emp) use ($children) {
            $emp->subordinates    = $children->get($emp->employee_id, collect());
            $emp->peer_candidates = $this->buildPeerCandidatesFor($emp->employee_id);
            return $emp;
        });

        if ($selfRow) {
            $selfRow->subordinates = $datas;
        }

        /* ===========================================
        * 4) MERGE PENDING APPROVAL EMPLOYEE
        * =========================================== */
        $pendingRequests = ApprovalRequest::where('category','Proposed360')
            ->where('period', $period)
            ->where('current_approval_id', $authEmpId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('employee_id')
            ->map->first();

        $pendingEmployeeIds = $pendingRequests->keys()->map('strval')->all();
        $existingIds        = $datas->pluck('employee_id')->map('strval')->all();

        $missingIds = array_values(array_diff($pendingEmployeeIds, $existingIds));

        $pendingEmployees = collect();

        if (!empty($missingIds)) {
            $pendingEmployees = EmployeeAppraisal::select(
                    'id','employee_id','fullname','designation_name',
                    'manager_l1_id','manager_l2_id'
                )
                ->with(['appraisalLayer'=>fn($q)=>$q->whereIn('layer_type',['peers','subordinate'])])
                ->whereIn('employee_id', $missingIds)
                ->get()
                ->map(function ($emp) {
                    // default kosong, nanti di rebuild
                    $emp->subordinates    = collect();
                    $emp->peer_candidates = collect();
                    return $emp;
                });
        }

        $datas = $datas->merge($pendingEmployees)->unique('employee_id')->values();


        /* ===========================================
        * 5) REBUILD SUBORDINATES + PEERS UNTUK SEMUA DATAS
        * =========================================== */
        $allEmpIds = $datas->pluck('employee_id')->all();

        $childrenRaw = EmployeeAppraisal::select(
                'id','employee_id','fullname','designation_name',
                'manager_l1_id','manager_l2_id'
            )
            ->where(function ($q) use ($allEmpIds) {
                $q->whereIn('manager_l1_id', $allEmpIds)
                ->orWhereIn('manager_l2_id', $allEmpIds);
            })
            ->get();

        $childrenMap = collect();
        foreach ($allEmpIds as $eid) {
            $childrenMap[$eid] = $childrenRaw
                ->where('manager_l1_id', $eid)
                ->merge($childrenRaw->where('manager_l2_id', $eid))
                ->unique('employee_id')
                ->values();
        }

        // final transform
        $datas->transform(function ($emp) use ($childrenMap) {
            $emp->subordinates    = $childrenMap->get($emp->employee_id, collect());
            $emp->peer_candidates = $this->buildPeerCandidatesFor($emp->employee_id);
            return $emp;
        });


        /* ===========================================
        * 6) AMBIL APPROVALREQUEST utk SELF + DATAS
        * =========================================== */
        $employeeKeys = collect();

        if ($selfRow) {
            $employeeKeys->push((string)$selfRow->employee_id);
        }

        $employeeKeys = $employeeKeys
            ->merge($datas->pluck('employee_id')->map('strval'))
            ->unique()
            ->values();

        $approvals = ApprovalRequest::with(['manager','initiated'])
            ->select('id','form_id','current_approval_id','approval_flow_id','current_step','employee_id',
                    'status','created_by','created_at','category','period','sendback_messages')
            ->where('category','Proposed360')
            ->where('period', $period)
            ->whereIn('employee_id', $employeeKeys)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('employee_id')
            ->map->first();

        // tempel approval_request & my_approval
        $datas->transform(function ($emp) use ($approvals, $pendingRequests) {
            $id = (string)$emp->employee_id;
            $emp->approval_request = $approvals->get($id);
            $emp->my_approval      = $pendingRequests->get($id);
            return $emp;
        });

        // === 6b) KONVERSI current_approval_id -> role -> kandidat approver ===
        $datas->transform(function ($emp) use ($flowRow) {

            $approval = $emp->approval_request ?? null;
            if (!$approval) return $emp;

            $cur = (string) ($approval->current_approval_id ?? '');

            // hanya proses jika current_approval_id BUKAN angka
            if ($cur !== '' && !ctype_digit($cur)) {

                $flowName = $approval->approval_flow_id;
                $stepName = $approval->current_step; // current_approval_id = step_name
                
                // ambil role dari ApprovalFlowStep
                $roleNames = $this->getRoleNameByStep($flowName, $stepName);                
                
                if (!empty($roleNames)) {
                    $candidateMap = $this->buildRoleCandidatesMap($roleNames);

                    $approval->current_approval_candidates = $candidateMap;
                } else {
                    $approval->current_approval_candidates = [];
                }
            }

            return $emp;
        });


        if ($selfRow) {
            $selfRow->approval_request = $approvals->get((string)$selfRow->employee_id);
            $selfRow->my_approval      = $pendingRequests->get((string)$selfRow->employee_id);
        }


        /* ===========================================
        * 7) PREFILL PROPOSED360 (PEERS & SUBORDINATES)
        * =========================================== */
        $formIds = collect();

        if (!empty($selfRow?->approval_request?->form_id)) {
            $formIds->push($selfRow->approval_request->form_id);
        }

        $datas->each(function ($emp) use ($formIds) {
            if (!empty($emp->approval_request?->form_id)) {
                $formIds->push($emp->approval_request->form_id);
            }
        });

        $formIds = $formIds->unique()->filter()->values();

        $txMap = $formIds->isNotEmpty()
            ? Proposed360::select('id','peers','subordinates')
                ->whereIn('id', $formIds)
                ->get()
                ->map(function ($t) {
                    $t->peers        = is_string($t->peers) ? json_decode($t->peers, true) : ($t->peers ?? []);
                    $t->subordinates = is_string($t->subordinates) ? json_decode($t->subordinates, true) : ($t->subordinates ?? []);
                    return $t;
                })
                ->keyBy('id')
            : collect();

        if ($selfRow && !empty($selfRow->approval_request?->form_id)) {
            if ($tx = $txMap->get($selfRow->approval_request->form_id)) {
                $selfRow->selected_peers        = collect($tx->peers)->filter()->values()->take(3)->all();
                $selfRow->selected_subordinates = collect($tx->subordinates)->filter()->values()->take(3)->all();
            }
        }

        $datas->each(function ($emp) use ($txMap) {
            if (!empty($emp->approval_request?->form_id)) {
                if ($tx = $txMap->get($emp->approval_request->form_id)) {
                    $emp->selected_peers        = collect($tx->peers)->filter()->values()->take(3)->all();
                    $emp->selected_subordinates = collect($tx->subordinates)->filter()->values()->take(3)->all();
                }
            }
        });

        /* ===========================================
        * 8) RETURN VIEW
        * =========================================== */
        $parentLink = __('Propose 360');
        $link       = __('Propose List');
        $peers      = collect();

        // dd($datas);

        return view('pages.proposed-360.app', compact(
            'parentLink','link','datas','peers','subordinates','self','selfPeers','period','selfEnabled'
        ));
    }


/* ===================== HELPERS ===================== */
/**
 * Bangun kandidat peers NON-reportee untuk target tertentu:
 * - exclude seluruh L-1 & L-2 target
 * - exclude semua karyawan yg L1-nya berada di bawah L2 = target (L1 under target)
 * - exclude diri sendiri
 */

private function getRoleNameByStep(int|string $flowId, string $stepName)
{
    if (empty($flowId) || trim($stepName) === '') {
        return [];
    }

    $cacheKey = "approval.flowstep.role.{$flowId}." . strtolower($stepName);

    return cache()->remember($cacheKey, 60, function () use ($flowId, $stepName) {

        // Ambil approver_role langsung dari ApprovalFlowStep
        $roleString = \App\Models\ApprovalFlowStep::query()
            ->where('approval_flow_id', $flowId)
            ->whereRaw('LOWER(step_name) = ?', [strtolower($stepName)])
            ->value('approver_role');

        if (!$roleString) {
            return [];
        }

        // Support multiple roles: "Manager, HRBP"
        return collect($roleString)
            ->map(fn($r) => trim($r))
            ->filter()
            ->values()
            ->all();
    });
}




private function buildPeerCandidatesFor(string $targetEmpId): \Illuminate\Support\Collection
{
    // --- 1) Ambil L1 & L2 si target
    $target = EmployeeAppraisal::query()
        ->select('employee_id','manager_l1_id','manager_l2_id')
        ->where('employee_id', $targetEmpId)
        ->first();

    $targetL1 = $target?->manager_l1_id ? (string) $target->manager_l1_id : null;
    $targetL2 = $target?->manager_l2_id ? (string) $target->manager_l2_id : null;

    // --- 2) Ambil row L1-nya target untuk dapatkan L1 & L2 dari L1 target (harus ikut di-exclude)
    $targetL1Row = null;
    $targetL1_L1 = null;
    $targetL1_L2 = null;
    if ($targetL1) {
        $targetL1Row = EmployeeAppraisal::query()
            ->select('employee_id','manager_l1_id','manager_l2_id')
            ->where('employee_id', $targetL1)
            ->first();

        $targetL1_L1 = $targetL1Row?->manager_l1_id ? (string) $targetL1Row->manager_l1_id : null; // L1 dari L1 target
        $targetL1_L2 = $targetL1Row?->manager_l2_id ? (string) $targetL1Row->manager_l2_id : null; // L2 dari L1 target
    }

    // --- 3) Kumpulan L1 yang berada di bawah target (target sebagai L2)
    $l1UnderTarget = EmployeeAppraisal::query()
        ->select('employee_id','manager_l1_id','manager_l2_id')
        ->where('manager_l2_id', $targetEmpId)
        ->get();

    // --- 4) Atasan (L1 & L2) dari setiap L1 di bawah target
    $superiorsOfL1s = collect();
    foreach ($l1UnderTarget as $l1Row) {
        if (!empty($l1Row->manager_l1_id)) $superiorsOfL1s->push((string) $l1Row->manager_l1_id);
        if (!empty($l1Row->manager_l2_id)) $superiorsOfL1s->push((string) $l1Row->manager_l2_id);
    }

    // --- 5) Semua reportee langsung target (L-1 & L-2)
    $directReporteesOfTarget = EmployeeAppraisal::query()
        ->where(function ($q) use ($targetEmpId) {
            $q->where('manager_l1_id', $targetEmpId)
              ->orWhere('manager_l2_id', $targetEmpId);
        })
        ->pluck('employee_id');

    // --- 6) Semua reportee dari L1-nya target (manager_l1_id/manager_l2_id = targetL1)
    $reporteesOfTargetL1 = collect();
    if ($targetL1) {
        $reporteesOfTargetL1 = EmployeeAppraisal::query()
            ->where(function ($q) use ($targetL1) {
                $q->where('manager_l1_id', $targetL1)
                  ->orWhere('manager_l2_id', $targetL1);
            })
            ->pluck('employee_id');
    }

    // --- 7) Susun exclusion list
    $exclude = collect()
        ->merge([$targetEmpId])                          // diri sendiri
        ->when($targetL1, fn($c) => $c->push($targetL1)) // L1 target
        ->when($targetL2, fn($c) => $c->push($targetL2)) // L2 target
        ->when($targetL1_L1, fn($c) => $c->push($targetL1_L1)) // L1 dari L1 target  ✅
        ->when($targetL1_L2, fn($c) => $c->push($targetL1_L2)) // L2 dari L1 target  ✅
        ->merge($directReporteesOfTarget)                // semua reportee target (L-1/L-2)
        // ->merge($reporteesOfTargetL1)                    // semua reportee dari L1 target
        ->merge($l1UnderTarget->pluck('employee_id'))    // L1 di bawah target
        ->merge($superiorsOfL1s)                         // L1 & L2 dari setiap L1 di bawah target
        ->filter()
        ->unique()
        ->values()
        ->all();

    // --- 8) Kandidat peers = semua karyawan yang TIDAK ada di exclusion list
    return EmployeeAppraisal::select('id','employee_id','fullname','designation_name','manager_l1_id','manager_l2_id')
        ->whereNotIn('employee_id', $exclude)
        ->get();
}



    private function flowAllowsSelf(?\App\Models\Flow $flow): bool
    {
        if (!$flow) return false;
        $json = $flow->initiator ?? $flow->initiators ?? '[]';
        $arr  = json_decode($json, true);
        if (!is_array($arr)) return false;

        return collect($arr)->contains(function ($it) {
            return strtolower((string)data_get($it, 'type')) === 'state'
                && strtolower((string)data_get($it, 'state_key')) === 'self';
        });
    }
    /**
     * Ambil daftar assignment id dari kolom json di table flows.
     */
    private function parseAssignmentIds(?Flow $flow): array
    {
        if (!$flow) return [];
        $raw = $flow->assignment_ids ?? $flow->assignments ?? $flow->assignment ?? '[]';
        $arr = json_decode($raw, true);
        if (!is_array($arr)) return [];
        return collect($arr)->map(fn($v)=>(string)$v)->filter()->unique()->values()->all();
    }

    /**
     * Load rule-set ONLY untuk TEAM (manager_l1_id scope).
     * Return: array of rule-set; tiap rule-set akan di-OR pada query.
     */
    private function loadTeamAssignments(array $assignmentIds): array
    {
        if (empty($assignmentIds)) return [];

        // Ambil hanya kolom yang dipakai + skip soft-deleted
        $rows = DB::table('assignments')
            ->whereIn('id', $assignmentIds)
            ->whereNull('deleted_at')
            ->get(['id','restriction']);

        $ruleSets = [];

        foreach ($rows as $r) {
            $json = $r->restriction ?? '{}';

            // Decode aman
            $arr = json_decode($json, true);
            if (!is_array($arr)) {
                // fallback kecil (jika ada string tunggal)
                $arr = [];
            }

            // Normalisasi: semua nilai jadi array string unik (trim)
            $norm = [];
            foreach ($arr as $key => $val) {
                if (is_array($val)) {
                    $norm[$key] = array_values(array_unique(array_map(
                        fn($v) => (string) trim((string) $v),
                        $val
                    )));
                } elseif ($val !== null && $val !== '') {
                    $norm[$key] = [(string) trim((string) $val)];
                }
            }

            if (!empty($norm)) {
                $ruleSets[] = $norm;
            }
        }

        // Contoh ruleSets: [
        //   ['group_company'=>['KPN Corporation'], 'job_level'=>['4A','4B','7A',...]],
        //   ['department_id'=>['10','11']]
        // ]
        return $ruleSets;
    }


    /**
     * Terapkan satu rule-set (AND di dalamnya) ke query EmployeeAppraisal.
     * Digunakan di dalam orWhere wrapper (OR antar rule-set).
     */
    private function applySingleRuleToQuery($q, array $rules, string $initiatorEmpId): void
    {
        // 1) Hanya bawahan langsung inisiator (TEAM scope)
        $q->where('manager_l1_id', $initiatorEmpId);

        // Helper: cache hasColumn untuk tabel employee_appraisal
        $table = 'employees_pa';
        $hasCol = (function() use ($table) {
            static $cache = [];
            return function(string $col) use ($table, &$cache): bool {
                if (!array_key_exists($col, $cache)) {
                    $cache[$col] = Schema::hasColumn($table, $col);
                }
                return $cache[$col];
            };
        })();

        // Utility: normalisasi array nilai -> string, unik, non-kosong
        $norm = function($arr): array {
            if (!is_array($arr)) return [];
            $vals = array_map(static fn($v) => trim((string)$v), $arr);
            $vals = array_filter($vals, static fn($v) => $v !== '');
            return array_values(array_unique($vals));
        };

        // 3) Helper umum: pilih nilai dari beberapa key rule, terapkan ke kolom pertama yang tersedia
        $applySet = function(array $candidateCols, array $ruleKeys) use ($q, $rules, $norm, $hasCol) {
            // ambil nilai pertama yang tersedia di rules
            $vals = [];
            foreach ($ruleKeys as $k) {
                if (!empty($rules[$k]) && is_array($rules[$k])) {
                    $vals = $norm($rules[$k]);
                    if (!empty($vals)) break;
                }
            }
            if (empty($vals)) return;

            // pilih kolom pertama yang eksis di tabel employee_appraisal
            foreach ($candidateCols as $col) {
                if ($hasCol($col)) {
                    $q->whereIn($col, $vals);
                    break;
                }
            }
        };

        // Designation/Title
        $applySet(
            ['designation_code','designation_name'],
            ['designation_code','designation_name']
        );

        // Location
        $applySet(
            ['work_area_code'],
            ['work_area_code']
        );

        // Group Company (contoh restriction: "group_company": ["KPN Corporation"])
        $applySet(
            ['group_company','group_company_code'],
            ['group_company','group_company_code']
        );

        // Job Level (contoh restriction: "job_level": ["4A","4B",...])
        $applySet(
            ['job_level'],
            ['job_level']
        );
    }


    /**
     * Bangun map kandidat approver untuk role (fullname (employee_id)).
     */
    private function buildRoleCandidatesMap(array $roleNames)
    {
        $map = collect();

        // Spatie
        if (
            class_exists(\Spatie\Permission\Models\Role::class) &&
            Schema::hasTable('model_has_roles')
        ) {
            $roleModels   = \Spatie\Permission\Models\Role::whereIn('name', $roleNames)
                            ->get(['id','name']);
            $roleIdByName = $roleModels->pluck('id','name');

            if ($roleIdByName->isNotEmpty()) {
                $rows    = DB::table('model_has_roles')
                            ->whereIn('role_id', $roleIdByName->values())
                            ->get(['role_id','model_type','model_id']);

                $userIds = $rows->pluck('model_id')->unique()->values();
                $userEmp = DB::table('users')->whereIn('id',$userIds)
                            ->pluck('employee_id','id');

                $empMap = EmployeeAppraisal::whereIn(
                    'employee_id',
                    array_filter($userEmp->values()->all())
                )
                ->get()
                ->keyBy('employee_id');

                foreach ($roleIdByName as $rName => $rId) {
                    $empIds = $rows->where('role_id',$rId)
                        ->map(fn($r)=>(string) ($userEmp[$r->model_id] ?? ''))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $labels = collect($empIds)->map(function ($eid) use ($empMap) {
                        return ($empMap[$eid]->fullname ?? $eid).' ('.$eid.')';
                    })->toArray();

                    $map[$rName] = $labels;
                }
            }
        }

        // Fallback pivot umum
        if ($map->isEmpty()) {
            $roles = DB::table('roles')
                ->whereIn('name', $roleNames)
                ->get(['id','name']);

            if ($roles->isNotEmpty()) {
                $pivot = collect(['role_user','user_roles'])
                    ->filter(fn($t)=>Schema::hasTable($t));

                $roleIdByName = $roles->mapWithKeys(fn($r)=>[
                    $r->name ?? $r->name => $r->id
                ]);

                $all = [];
                foreach ($pivot as $p) {
                    $rows = DB::table($p)
                        ->whereIn('role_id',$roleIdByName->values())
                        ->get(['role_id','user_id']);

                    foreach ($rows as $r) {
                        $all[$r->role_id][] = $r->user_id;
                    }
                }

                $userEmp = DB::table('users')
                    ->whereIn('id', array_merge(...array_values($all ?: [])))
                    ->pluck('employee_id','id');

                $empMap = EmployeeAppraisal::whereIn(
                    'employee_id',
                    array_values(array_filter($userEmp->values()->all()))
                )
                ->get()
                ->keyBy('employee_id');

                foreach ($roleIdByName as $rName => $rId) {
                    $uids = array_unique($all[$rId] ?? []);
                    $labels = [];

                    foreach ($uids as $uid) {
                        $eid = (string)($userEmp[$uid] ?? '');
                        if ($eid === '') continue;
                        $labels[] = ($empMap[$eid]->fullname ?? $eid).' ('.$eid.')';
                    }

                    $map[$rName] = $labels;
                }
            }
        }

        return $map;
    }

    public function store(Request $request, Proposed360Service $service, ApprovalEngine $engine)
    {
        // Normalisasi input (support legacy "subs")
        $subsInput = $request->input('subordinates', []);

        $subordinates = collect($subsInput)
            ->map(fn($v) => is_null($v) || $v === '' ? null : (string)$v) // cast & ubah "" → null
            ->filter()   // buang null/empty
            ->unique()   // buang duplikat
            ->values()
            ->take(3)
            ->all();

        $peersInput = $request->input('peers', []);

        $peers = collect($peersInput)
            ->map(fn($v) => is_null($v) || $v === '' ? null : (string)$v) // cast & ubah "" → null
            ->filter()   // buang null/empty
            ->unique()   // buang duplikat
            ->values()
            ->take(3)
            ->all();

        $request->merge(['peers' => $peers, 'subordinates' => $subordinates]);

        $data = $request->validate([
            'employee_id'=>['required'],
            'scope'=>['required','in:self,team'],
            'peers'=>['array','max:3'],
            'peers.*'=>['distinct'],
            'subordinates'=>['array','max:3'],
            'subordinates.*'=>['distinct'],
            // 'managers'=>['array','max:3'],
            // 'managers.*'=>['integer','distinct'],
            'appraisal_year'=>['required'],
        ]);

        $trace = (string) Str::uuid();
        Log::info('proposed360.store.start', ['trace'=>$trace,'actor'=>Auth::user()->employee_id,'employee_id'=>$data['employee_id'],'scope'=>$data['scope'],'year'=>$data['appraisal_year']]);
        DB::beginTransaction();
        try {
            $trx = $service->submit($data,$engine);
            DB::commit();
            Log::info('proposed360.store.success', ['trace'=>$trace,'form_id'=>$trx->id,'status'=>$trx->status]);
            return redirect()->route('proposed360')->with('success','Pengajuan 360 dikirim');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('proposed360.store.error', ['trace'=>$trace,'message'=>$e->getMessage(),'code'=>$e->getCode(),'line'=>$e->getLine()]);
            return back()->withInput()->withErrors(['error'=>'Gagal mengirim pengajuan']);
        }
    }

    public function action(Request $request, Proposed360Service $service, ApprovalEngine $engine)
    {
        $data = $request->validate([
            'form_id'=>['required','uuid','exists:proposed_360_transactions,id'],
            'action'=>['required','in:APPROVE,REJECT'],
            'sendback_to'=>['nullable','string','max:50'],
        ]);

        $message  = (string) $request->input('sendback_message', '');
        $trace = (string) Str::uuid();
        Log::info('proposed360.action.start', ['trace'=>$trace,'actor'=>Auth::user()->employee_id,'form_id'=>$data['form_id'],'action'=>$data['action']]);
        DB::beginTransaction();
        try {
            if ($data['action']==='APPROVE') {
                // APPROVE dengan overwrite pilihan terbaru dari form:
                $service->approve($data['form_id'], $engine, $request->input('peers'), $request->input('subordinates'));
            } else {
                $service->reject($data['form_id'], $engine, $data['sendback_to'] ?? null, $message);
            }
            DB::commit();
            Log::info('proposed360.action.success', ['trace'=>$trace,'form_id'=>$data['form_id'],'action'=>$data['action']]);
            return redirect()->route('proposed360')->with('success','Approval processed successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('proposed360.action.error', ['trace'=>$trace,'form_id'=>$data['form_id'],'action'=>$data['action'],'message'=>$e->getMessage(),'code'=>$e->getCode(),'line'=>$e->getLine()]);
            return back()->withErrors(['error'=>'Gagal memproses approval']);
        }
    }

    public function resubmit(Request $request, ApprovalEngine $engine)
    {
        // --- Normalisasi input persis seperti di store() ---
        $subsInput = $request->input('subordinates', []);
        $subordinates = collect($subsInput)
            ->map(fn($v) => is_null($v) || $v === '' ? null : (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->take(3)
            ->all();

        $peersInput = $request->input('peers', []);
        $peers = collect($peersInput)
            ->map(fn($v) => is_null($v) || $v === '' ? null : (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->take(3)
            ->all();

        // inject hasil normalisasi ke payload agar tervalidasi konsisten
        $request->merge(['peers' => $peers, 'subordinates' => $subordinates]);

        // --- Validasi (disamakan dengan store) ---
        $data = $request->validate([
            'form_id'        => ['required','string','exists:proposed_360_transactions,id'],
            'employee_id'    => ['required'],
            'scope'          => ['required','in:self,team'],
            'peers'          => ['array','max:3'],
            'peers.*'        => ['distinct'],
            'subordinates'   => ['array','max:3'],
            'subordinates.*' => ['distinct'],
            'appraisal_year' => ['required'],
            // 'managers'     => ['array','max:3'],
            // 'managers.*'   => ['distinct'],
        ]);

        $actorEmpId = Auth::user()->employee_id;
        $actorId = Auth::id();
        $trace = (string) Str::uuid();

        Log::info('proposed360.resubmit.start', [
            'trace'       => $trace,
            'actor'       => $actorEmpId,
            'form_id'     => $data['form_id'],
            'employee_id' => $data['employee_id'],
            'scope'       => $data['scope'],
            'year'        => $data['appraisal_year'],
        ]);

        DB::beginTransaction();
        try {
            // Pastikan status SENDBACK (biar aman di sisi server)
            $req = ApprovalRequest::where('form_id', $data['form_id'])->lockForUpdate()->firstOrFail();
            if (strtoupper($req->status) !== 'SENDBACK') {
                throw ValidationException::withMessages(['status' => 'Transaksi bukan status Sendback.']);
            }

            // Update transaksi Proposed360 dengan hasil normalisasi terbaru
            $trx = Proposed360::findOrFail($data['form_id']);
            $trx->update([
                'peers'        => $data['peers'],
                'subordinates' => $data['subordinates'],
                'status'       => 'PENDING',       // kembali pending setelah revise
                'updated_by'   => $actorId,
            ]);

            // Kirim ulang ke engine (akan set status -> PENDING & hitung current approver lagi)
            $engine->resubmit($data['form_id'], $actorEmpId);

            DB::commit();
            Log::info('proposed360.resubmit.success', ['trace' => $trace, 'form_id' => $data['form_id']]);

            return redirect()->route('proposed360.index')->with('success', 'Pengajuan 360 direvisi & dikirim.');

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('proposed360.resubmit.error', [
                'trace' => $trace, 'form_id' => $data['form_id'] ?? null,
                'msg' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine()
            ]);
            return back()->withInput()->withErrors(['error' => 'Gagal resubmit: '.$e->getMessage()]);
        }
    }

}
