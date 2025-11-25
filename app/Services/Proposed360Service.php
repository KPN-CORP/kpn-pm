<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\EmployeeAppraisal;
use App\Models\Flow;
use App\Models\Proposed360;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Proposed360Service
{
    public function submit(array $data, ApprovalEngine $engine): Proposed360
    {
        return DB::transaction(function () use ($data, $engine) {
            $actor   =  Auth::user()->employee_id;
            $targetId =  $data['employee_id'];
            if (($data['scope'] ?? 'self') === 'self' && $targetId !== $actor) {
                throw ValidationException::withMessages(['scope' => 'Scope self hanya untuk diri sendiri']);
            }

            $flowRow = Flow::where('module_transaction', 'Propose 360')->first();
            if (!$flowRow) throw ValidationException::withMessages(['flow' => 'Flow Propose 360 tidak ditemukan']);

            $initiators = json_decode($flowRow->initiator ?? $flowRow->initiators ?? '[]', true);
            if (!is_array($initiators) || empty($initiators)) {
                throw ValidationException::withMessages(['initiator' => 'Konfigurasi initiator tidak valid']);
            }
            
            $target = EmployeeAppraisal::select('employee_id','manager_l1_id','manager_l2_id')
            ->where('employee_id',$targetId)->firstOrFail();
            
            $scopeKey = $this->scopeToStateKey($data['scope'] ?? 'self'); // 'self' | 'manager_l1_id'
            $matched  = $this->selectInitiator($initiators, $scopeKey, $target, $actor);
            
            if (!$matched) throw ValidationException::withMessages(['initiator'=>'Akses inisiasi tidak diizinkan untuk scope ini']);
            $flowId =  data_get($matched,'approval_flow_id');
            if (!$flowId) throw ValidationException::withMessages(['approval_flow_id'=>'approval_flow_id tidak ditemukan pada initiator']);
            
            $flow = ApprovalFlow::with('steps')->findOrFail($flowId);
            if ($flow->steps->isEmpty()) throw ValidationException::withMessages(['steps'=>'Approval flow belum memiliki step']);

            $peers = array_slice(array_values(array_unique(array_filter($data['peers'] ?? [], fn($v)=>$v!==null && $v!==''))),0,3);
            $subs  = array_slice(array_values(array_unique(array_filter($data['subordinates'] ?? [], fn($v)=>$v!==null && $v!==''))),0,3);
            $mgrs  = array_slice(array_values(array_unique(array_filter($data['managers'] ?? [], fn($v)=>$v!==null && $v!==''))),0,3);

            $trx = Proposed360::create([
                'employee_id'          => $targetId,
                'proposer_employee_id' => $actor,
                'scope'                => $data['scope'],
                'peers'                => $peers,
                'subordinates'         => $subs,
                'managers'             => $mgrs,
                'status'               => 'PENDING',
                'approval_flow_id'     => $flow->id,
                'current_step'         => 1,
                'appraisal_year'       => (INT) $data['appraisal_year'],
                'notes'                => $data['notes'] ?? null,
                'created_by'           => Auth::id(),
                'updated_by'           => Auth::id(),
            ]);

            $engine->openForm($trx->id, 'Proposed360', $targetId, $flow->id, $trx->appraisal_year, $actor);
            return $trx;
        });
    }

    private function scopeToStateKey(string $scope): string
    {
        $scope = strtolower($scope);
        return match ($scope) {
            'self' => 'self',
            'team' => 'manager_l1_id', // mapping scope UI → state key
            default => $this->normalizeKey($scope),
        };
    }

    private function normalizeKey(?string $raw): ?string
    {
        if ($raw === null) return null;
        $k = strtolower(trim($raw));
        return match ($k) {
            'l1_manager_id', 'manager_l1', 'manager_l1_id' => 'manager_l1_id',
            'l2_manager_id', 'manager_l2', 'manager_l2_id' => 'manager_l2_id',
            'self' => 'self',
            default => $k,
        };
    }

    private function selectInitiator(array $initiators, string $scopeKey, EmployeeAppraisal $target, string $actorEmpId): ?array
    {
        $scopeKey = $this->normalizeKey($scopeKey);

        return collect($initiators)->first(function ($init) use ($scopeKey, $target, $actorEmpId) {
            $type = data_get($init,'type');

            if ($type === 'state') {
                $initKey = $this->normalizeKey(data_get($init,'state_key'));
                if ($initKey !== $scopeKey) return false;

                return $this->matchState($initKey, $target, $actorEmpId);
            }

            if ($type === 'role') {
                $value = data_get($init,'value');
                return $this->actorHasRole($value);
            }

            return false;
        });
    }

    private function matchState(string $key, EmployeeAppraisal $target, string $actorEmpId): bool
    {
        if ($key === 'self') return $target->employee_id === $actorEmpId;
        if (in_array($key, ['manager_l1_id','manager_l2_id'], true)) {
            return  data_get($target, $key) ===  $actorEmpId;
        }
        return false;
    }

    private function actorHasRole($value): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($user, 'hasRole')) {
            return $user->hasRole($value);
        }

        $roleId = is_numeric($value)
            ?  $value
            : Role::where('code',$value)->orWhere('name',$value)->value('id');

        if (!$roleId) return false;

        foreach (['model_has_roles'] as $pivot) {
            if (!Schema::hasTable($pivot)) continue;
            $exists = DB::table($pivot)->when($pivot==='model_has_roles', function ($q) use ($user,$roleId) {
                $q->where('model_type', get_class($user))->where('model_id', $user->getKey())->where('role_id',$roleId);
            }, function ($q) use ($user,$roleId) {
                $q->where('user_id', $user->getKey())->where('role_id',$roleId);
            })->exists();
            if ($exists) return true;
        }
        return false;
    }

    // Old approve method
    // public function approve(string $formId, ApprovalEngine $engine): void
    // {
    //     DB::transaction(function () use ($formId,$engine) {
    //         $actor = Auth::user()->employee_id;
    //         $engine->approve($formId,$actor);
    //     });
    // }

    public function approve(
        string $formId,
        ApprovalEngine $engine,
        ?array $peers = null,
        ?array $subordinates = null,
        $havingSubs
    ): void {
        DB::transaction(function () use ($formId, $engine, $peers, $subordinates, $havingSubs) {
            $actorEmpId = (string) Auth::user()->employee_id;

            // --- Normalisasi input (hanya jika parameter dikirim) ---
            $norm = function (?array $arr): array {
                if (!is_array($arr)) return [];
                return collect($arr)
                    ->map(fn($v) => is_null($v) || $v === '' ? null : (string) $v)
                    ->filter()
                    ->unique()
                    ->values()
                    ->take(3)
                    ->all();
            };

            
            $peersNorm = $norm($peers);
            $subsNorm  = $norm($subordinates);
            
            // --- Validasi (wajib layer-1 hanya jika field dikirim ke service) ---
            if (is_array($peers) || is_array($subordinates)) {
                $rules = [
                    'peers'     => ['array','max:3'],
                    'peers.0'   => ['required'],   // peers L1 wajib
                    'peers.*'   => ['distinct'],

                    'subordinates' => ['array','max:3'],
                    'subordinates.*' => ['distinct'],
                ];

                if ($havingSubs) {
                    $rules['subordinates.0'] = ['required'];
                }

                Validator::make(
                    ['peers' => $peersNorm, 'subordinates' => $subsNorm],
                    $rules,
                    [
                        'peers.0.required'         => 'Peers layer 1 wajib diisi.',
                        'subordinates.0.required'  => 'Subordinate layer 1 wajib diisi.',
                    ]
                )->validate();
            }
            
            // --- Update transaksi bila ada input baru ---
            if (is_array($peers) || is_array($subordinates)) {
                $trx = \App\Models\Proposed360::lockForUpdate()->findOrFail($formId);
                $payload = [];
                if (is_array($peers))        $payload['peers'] = $peersNorm;
                if (is_array($subordinates)) $payload['subordinates'] = $subsNorm;
                
                if (!empty($payload)) {
                    // Catatan: asumsikan casts JSON di model; jika tidak, json_encode terlebih dulu.
                    $payload['updated_by'] = Auth::id();
                    $trx->update($payload);
                    Log::info('proposed360.approve.update', [
                        'form_id' => $formId,
                        'update'  => array_keys($payload),
                    ]);
                }
            }
            
            // --- Lanjut approve via engine (akan trigger finalize di engine) ---
            $engine->approve($formId, $actorEmpId);
        });
    }


    public function reject(string $formId, ApprovalEngine $engine, ?string $sendbackTo=null, ?string $message=null): void
    {
        DB::transaction(function () use ($formId,$engine,$sendbackTo,$message) {
            $actor = Auth::user()->employee_id;
            $engine->reject($formId,$actor,$sendbackTo,$message);
        });
    }

    public function applyFromApprovalRequest(ApprovalRequest $req, string $actorEmpId): void
    {
        DB::transaction(function () use ($req, $actorEmpId) {
            // jaga-jaga
            if (strcasecmp($req->status, 'Approved') !== 0) return;

            
            $trx   = Proposed360::lockForUpdate()->findOrFail($req->form_id);
            $empId = (string) $trx->employee_id;
            
            // normalisasi list & limit 3
            $peers = collect($this->toArray($trx->peers))->filter()->unique()->values()->take(3)->all();
            $subs  = collect($this->toArray($trx->subordinates))->filter()->unique()->values()->take(3)->all();
            $mgrs  = collect($this->toArray($trx->managers))->filter()->unique()->values()->take(3)->all();

            // tulis ke approval_layer_appraisals per baris (manager/peers/subordinate, layer 1..3)
            $this->syncRows($empId, 'peers',       $peers, $actorEmpId);
            $this->syncRows($empId, 'subordinate', $subs,  $actorEmpId);
            // $this->syncRows($empId, 'manager',     $mgrs,  $actorEmpId);

            // tandai transaksi
            $trx->update(['status' => 'APPROVED', 'updated_by' => Auth::id()]);
        });
    }

    private function syncRows(string $employeeId, string $type, array $ids): void
    {
        $now   = now();
        $user = (string) Auth::id(); // <<< pakai user id

        $keepLayers = [];

        foreach ([1,2,3] as $i) {
            $approver = $ids[$i-1] ?? null;

            $key = [
                'employee_id' => (string) $employeeId,
                'layer_type'  => (string) $type,   // 'manager' | 'peers' | 'subordinate'
                'layer'       => (int) $i,
            ];

            if ($approver) {
                // Cek apakah baris sudah ada
                $row = ApprovalLayerAppraisal::where($key)->first();

                if ($row) {
                    // UPDATE → isi kolom update saja
                    $row->fill([
                        'approver_id' => (string) $approver,
                        'updated_by'  => $user,
                        'updated_at'  => $now,
                    ])->save();
                } else {
                    // CREATE → JANGAN isi updated_* (sesuai requirement)
                    ApprovalLayerAppraisal::create($key + [
                        'approver_id' => (string) $approver,
                        'created_by'  => Auth::id(),
                        'created_at'  => $now,
                    ]);
                }

                $keepLayers[] = $i;
            } else {
                // kosong → hapus layer ini
                ApprovalLayerAppraisal::where($key)->delete();
            }
        }

        // Bersihkan sisa layer 1..3 yang tidak dipakai
        ApprovalLayerAppraisal::where('employee_id', $employeeId)
            ->where('layer_type', $type)
            ->whereBetween('layer', [1, 3])
            ->when(!empty($keepLayers), fn($q) => $q->whereNotIn('layer', $keepLayers))
            ->delete();

        // Pastikan tidak ada layer > 3
        ApprovalLayerAppraisal::where('employee_id', $employeeId)
            ->where('layer_type', $type)
            ->where('layer', '>', 3)
            ->delete();
    }


    private function toArray($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v)) {
            $d = json_decode($v, true);
            return json_last_error() === JSON_ERROR_NONE ? (array)$d : (strlen($v) ? [$v] : []);
        }
        return (array) $v;
    }
}
