<?php

namespace App\Http\Controllers;

use App\Models\ApprovalFlow;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalLog;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Flow;
use App\Models\Proposed360;
use App\Services\ApprovalEngine;
use App\Services\AppService;
use App\Services\Proposed360Service;
use App\Support\AppraisalManagerResolver;
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

        // Satu-satunya query employees_pa di halaman ini. Semua daftar
        // (self, tim, bawahan, pending, anak, kandidat peers) diambil dari
        // koleksi ini; kolom rule assignment ikut dimuat supaya filter
        // assignment bisa dilakukan di memori.
        $ruleColumns  = $this->teamRuleColumns();
        $allEmployees = EmployeeAppraisal::select(array_values(array_unique(array_merge(
                ['id','employee_id','fullname','designation_name','manager_l1_id','manager_l2_id'],
                array_values($ruleColumns)
            ))))
            ->orderBy('id')
            ->get()
            ->keyBy('employee_id');

        // Atasan L1/L2 di modul ini mengikuti Layer Setting appraisal
        // (approval_layer_appraisals, layer_type 'manager'), bukan kolom
        // employees_pa.manager_l1_id/l2_id yang tidak ikut ter-update.
        AppraisalManagerResolver::applyToAll($allEmployees);

        // Level user login terhadap tiap karyawan, dari hasil Layer Setting:
        // 1 = user adalah L1 (atasan langsung), 2 = user adalah L2.
        $levelOf = function ($emp) use ($authEmpId): ?int {
            if ((string) $emp->manager_l1_id === $authEmpId) return 1;
            if ((string) $emp->manager_l2_id === $authEmpId) return 2;
            return null;
        };

        // Baris yang ditampilkan di-clone supaya atribut tambahan
        // (subordinates, approval_request, ...) tidak menempel ke
        // $allEmployees yang juga dipakai sebagai kandidat peers.
        $pick = fn ($ids) => collect($ids)
            ->map('strval')
            ->sort(SORT_STRING)
            ->map(fn ($id) => $allEmployees->get((string) $id))
            ->filter()
            ->map(fn ($e) => clone $e)
            ->values();

        $self = collect();
        $selfRow = $pick([$authEmpId])->first();

        /* ===========================================
        * 2) TEAM (L1) & SUBORDINATES (L1 + L2)
        * =========================================== */
        $datas = collect();
        $subordinates = collect();

        if ($selfRow && !empty($teamRuleSets)) {
            $datas = $pick($allEmployees
                ->filter(fn ($e) => $levelOf($e) === 1
                    && $this->matchesAnyTeamRule($e, $teamRuleSets, $ruleColumns))
                ->keys());

            $subordinates = $allEmployees
                ->filter(fn ($e) => $levelOf($e) !== null)
                ->values();
        }

        /* ===========================================
        * 3) MERGE PENDING APPROVAL EMPLOYEE
        * =========================================== */
        $pendingRequests = ApprovalRequest::where('category','Proposed360')
            ->where('period', $period)
            ->where('current_approval_id', $authEmpId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('employee_id')
            ->map->first();

        $missingIds = array_values(array_diff(
            $pendingRequests->keys()->map('strval')->all(),
            $datas->pluck('employee_id')->map('strval')->all()
        ));

        $datas = $datas->merge($pick($missingIds))->unique('employee_id')->values();

        /* ===========================================
        * 4) LAYER PEERS/SUBORDINATE (satu query untuk semua baris tampil)
        * =========================================== */
        $displayIds = $datas->pluck('employee_id')
            ->when($selfRow, fn ($c) => $c->push($selfRow->employee_id))
            ->map('strval')->unique()->values();

        $layerRows = $displayIds->isEmpty()
            ? collect()
            : ApprovalLayerAppraisal::whereIn('employee_id', $displayIds)
                ->whereIn('layer_type', ['peers','subordinate'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($r) => (string) $r->employee_id);

        foreach ($datas->when($selfRow, fn ($c) => $c->concat([$selfRow])) as $emp) {
            $emp->setRelation('appraisalLayer', $layerRows->get((string) $emp->employee_id, collect()));
        }

        if ($selfRow) {
            $selfRow->peers        = $selfRow->appraisalLayer->where('layer_type','peers')->values();
            $selfRow->subordinates = $datas;
            $self                  = collect([$selfRow]);
        }

        /* ===========================================
        * 5) SUBORDINATES + PEERS UNTUK SEMUA DATAS
        * =========================================== */
        $byL1 = $allEmployees->groupBy(fn ($e) => (string) $e->manager_l1_id);
        $byL2 = $allEmployees->groupBy(fn ($e) => (string) $e->manager_l2_id);

        // Kandidat peers = semua karyawan dikurangi daftar exclude per target.
        // Daftar karyawan dikirim sekali ke browser (#proposed360-peer-data),
        // dropdown hanya membawa opsi terpilih; Select2 mencari & memaginasi
        // di sisi client. Dulu tiap dropdown berisi ~semua karyawan, sehingga
        // halaman approver bisa puluhan MB.
        $peerExclude = [];
        foreach ($datas->when($selfRow, fn ($c) => $c->concat([$selfRow])) as $emp) {
            $eid = (string) $emp->employee_id;
            $peerExclude[$eid] ??= $this->buildPeerExcludeFor($eid, $allEmployees, $byL1, $byL2);
        }

        $isPeerCandidate = fn ($targetId, $candidateId) => $candidateId !== null && $candidateId !== ''
            && is_array($peerExclude[(string) $targetId] ?? null)
            && $allEmployees->has((string) $candidateId)
            && !in_array((string) $candidateId, $peerExclude[(string) $targetId], true);

        $peerData = [
            'employees' => $allEmployees->map(fn ($e) => [(string) $e->employee_id, (string) $e->fullname])->values(),
            'exclude'   => $peerExclude,
        ];

        $datas->transform(function ($emp) use ($byL1, $byL2) {
            $eid = (string) $emp->employee_id;
            $emp->subordinates = $byL1->get($eid, collect())->sortBy('employee_id', SORT_STRING)
                ->merge($byL2->get($eid, collect())->sortBy('employee_id', SORT_STRING))
                ->unique('employee_id')
                ->values();
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

        return view('pages.proposed-360.app', compact(
            'parentLink','link','datas','peers','subordinates','self','period','selfEnabled',
            'allEmployees','peerData','isPeerCandidate'
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

/**
 * Daftar employee_id yang TIDAK boleh jadi peers untuk target:
 * - target sendiri, L1 & L2 target, L1 & L2 dari L1 target
 * - bawahan target (target sebagai L1 atau L2)
 * - L1/L2 dari karyawan yang L2-nya = target
 * Kandidat peers = semua karyawan di $allEmp dikurangi daftar ini.
 * null = target tidak ada di employees_pa (tidak ada kandidat).
 *
 * @return array<int, string>|null
 */
private function buildPeerExcludeFor(string $targetEmpId, Collection $allEmp, Collection $byL1, Collection $byL2): ?array
{
    if (!$allEmp->has($targetEmpId)) {
        return null;
    }

    $target   = $allEmp[$targetEmpId];
    $targetL1 = (string) ($target->manager_l1_id ?? '');
    $targetL2 = (string) ($target->manager_l2_id ?? '');

    $targetL1Row = $targetL1 !== '' ? $allEmp->get($targetL1) : null;

    $exclude = [$targetEmpId, $targetL1, $targetL2];

    if ($targetL1Row) {
        $exclude[] = (string) ($targetL1Row->manager_l1_id ?? '');
        $exclude[] = (string) ($targetL1Row->manager_l2_id ?? '');
    }

    // bawahan target (target sebagai L1 atau L2)
    foreach ($byL1->get($targetEmpId, collect()) as $emp) {
        $exclude[] = (string) $emp->employee_id;
    }

    // target sebagai L2: karyawannya + atasan-atasan mereka
    foreach ($byL2->get($targetEmpId, collect()) as $emp) {
        $exclude[] = (string) $emp->employee_id;
        $exclude[] = (string) ($emp->manager_l1_id ?? '');
        $exclude[] = (string) ($emp->manager_l2_id ?? '');
    }

    return array_values(array_unique(array_filter($exclude, fn ($v) => $v !== '')));
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
     * Kolom employees_pa yang dipakai rule assignment tim, per grup rule.
     * Tiap grup: daftar key rule (dicoba berurutan) => kolom pertama yang
     * benar-benar ada di tabel. Grup tanpa kolom yang ada dilewati.
     *
     * @return array<string, string>  rule-key-list ("a|b") => kolom
     */
    private function teamRuleColumns(): array
    {
        $groups = [
            'designation_code|designation_name' => ['designation_code','designation_name'],
            'work_area_code'                    => ['work_area_code'],
            'group_company|group_company_code'  => ['group_company','group_company_code'],
            'job_level'                         => ['job_level'],
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

        return $map;
    }

    /**
     * Cek satu karyawan terhadap rule-set assignment tim:
     * OR antar rule-set, AND antar grup di dalam satu rule-set.
     * Perbandingan mengikuti WHERE IN MySQL (collation *_ci): tidak peka
     * huruf besar/kecil dan spasi di belakang diabaikan.
     */
    private function matchesAnyTeamRule($emp, array $ruleSets, array $ruleColumns): bool
    {
        $canon = static fn ($v) => mb_strtolower(rtrim((string) $v));

        foreach ($ruleSets as $rules) {
            $ok = true;

            foreach ($ruleColumns as $keys => $col) {
                // nilai dari key rule pertama yang terisi
                $vals = [];
                foreach (explode('|', $keys) as $k) {
                    if (!empty($rules[$k]) && is_array($rules[$k])) {
                        $vals = array_values(array_filter(
                            array_map(static fn ($v) => trim((string) $v), $rules[$k]),
                            static fn ($v) => $v !== ''
                        ));
                        if (!empty($vals)) break;
                    }
                }
                if (empty($vals)) continue;

                $value = $emp->{$col};
                if ($value === null || !in_array($canon($value), array_map($canon, $vals), true)) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) return true;
        }

        return false;
    }


    /**
     * Hasil buildRoleCandidatesMap per kombinasi role, untuk satu request.
     * Dipanggil per baris tabel dengan role yang hampir selalu sama, dan
     * setiap panggilan butuh 4–6 query (termasuk Schema::hasTable).
     *
     * @var array<string, \Illuminate\Support\Collection>
     */
    private array $roleCandidatesCache = [];

    private function buildRoleCandidatesMap(array $roleNames)
    {
        $key = implode('|', array_map('strval', $roleNames));

        $this->roleCandidatesCache[$key] ??= $this->resolveRoleCandidatesMap($roleNames);

        // clone: pemanggil boleh mengubah map-nya tanpa mengotori cache.
        return clone $this->roleCandidatesCache[$key];
    }

    /**
     * Bangun map kandidat approver untuk role (fullname (employee_id)).
     */
    private function resolveRoleCandidatesMap(array $roleNames)
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
                $result = $service->approve($data['form_id'], $engine, $request->input('peers'), $request->input('subordinates'), $request->input('having_subs'));
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
