<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\EmployeeAppraisal;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ApprovalEngine
{
    // App\Services\ApprovalEngine.php

    public function openForm(string $formId, string $category, string $employeeId, int $flowId, int $period, string $actorEmpId): void
    {
        $steps = ApprovalFlowStep::with('flow')->where('approval_flow_id', $flowId)->orderBy('step_number')->get();
        $first = $steps->first();

        // Approver awal dihitung dari konteks initiator (actor)
        $current = $first
            ? $this->resolveApprover(
                $first,
                $initiatorEmpId = $actorEmpId,   // initiator
                $targetEmpId    = $employeeId,   // target form
                $proposerEmpId  = $actorEmpId    // proposer
            )
            : null;

        $approval = ApprovalRequest::create([
            'form_id'             => (string) $formId,
            'category'            => (string) $category,
            'current_approval_id' => isset($current) ? (string) $current : null, // bisa employee_id atau ROLE NAME
            'approval_flow_id'    => (int) $flowId,
            'total_steps'         => $steps->count(),
            'current_step'        => 1,
            'employee_id'         => (string) $employeeId,  // target (string)
            'status'              => 'Pending',
            'period'              => (int) $period,
            'created_by'          => Auth::id(),  // initiator (employee_id)
        ]);

        // === AUDIT LOG (OPEN) ===
        $this->auditLog([
            'approval_request_id' => $approval->id,
            'actor_employee_id'   => (string) $actorEmpId,
            'action'              => 'OPEN_FORM',
            'comments'            => 'Approval request created',
            'module'              => 'approval_request',
            'loggable_id'         => $approval->id,
            'loggable_type'       => ApprovalRequest::class,
            'flow_id'             => (int) $flowId,
            'step_from'           => null,
            'step_to'             => 1,
            'status_from'         => 'Draft',
            'status_to'           => 'Pending',
            'approver_from'       => null,
            'approver_to'         => isset($current) ? (string) $current : null,
            'actor_role'          => null, // isi jika perlu (mis. ambil dari roles user)
            'meta'                => [
                'form_id'              => (string) $formId,
                'category'             => (string) $category,
                'employee_id'   => (string) $employeeId,
                'initiator_id'=> (string) $actorEmpId,
                'period'               => (int) $period,
                'total_steps'          => $steps->count(),
                'current_approval_id'  => isset($current) ? (string) $current : null,
            ],
        ]);

        // log biasa (opsional)
        Log::info("Approval Request created: {$approval->id} for form {$formId} by employee {$actorEmpId}");
    }


    public function approve(string $formId, string $actorEmpId): void
    {
        $role = Auth::user()->roles?->first()?->name;

        /** @var \App\Models\ApprovalRequest $req */
        $req = ApprovalRequest::with(['initiated'])
            ->where('form_id', $formId)
            ->lockForUpdate()
            ->firstOrFail();
        if ($req->status !== 'Pending') return;

        $isOverride = $this->hasOverridePrivilege();

        // jika bukan override, tetap wajib lolos canActOn
        if (!$isOverride && !$this->canActOn($req, $actorEmpId)) return;

        // Snapshot
        $fromStep     = (int) $req->current_step;
        $fromApprover = (string) ($req->current_approval_id ?? null);

        $steps = ApprovalFlowStep::where('approval_flow_id', $req->approval_flow_id)
            ->orderBy('step_number')->get();

        // === ADMIN/ROLE OVERRIDE: langsung Approved penuh ===
        if ($isOverride) {
            $req->update([
                'status'              => 'Approved',
                'current_step'        => $steps->count(),
                'current_approval_id' => $role,
                'sendback_messages'   => null,
                'updated_by'          => Auth::id(),
            ]);

            $this->finalize($req, (string) $actorEmpId);

            $this->auditLog([
                'module'              => 'approval_request',
                'loggable_id'         => (string) $req->form_id,
                'loggable_type'       => ApprovalRequest::class,
                'approval_request_id' => $req->id,
                'actor_employee_id'   => (string) $actorEmpId,
                'actor_role'          => $fromApprover,
                'action'              => 'APPROVE_OVERRIDE',
                'status_from'         => 'Pending',
                'status_to'           => 'Approved',
                'flow_id'             => $req->approval_flow_id,
                'step_from'           => $fromStep,
                'step_to'             => (int) $req->current_step,
                'approver_from'       => $fromApprover,
                'approver_to'         => null,
                'meta' => [
                    'form_id'      => (string) $formId,
                    'category'     => $req->category,
                    'employee_id'  => $req->employee_id, // <-- perbaikan
                    'approver_id'  => (string) $actorEmpId,
                    'period'       => $req->period,
                    'total_steps'  => $steps->count(),
                    'override'     => true,
                    'final'        => true,
                ],
            ]);
            return;
        }

        // === Flow normal
        $isLast = $req->current_step >= $steps->count();

        if ($isLast) {
            $req->update([
                'status'            => 'Approved',
                'sendback_messages' => null,
                'updated_by'        => Auth::id(),
            ]);

            $this->finalize($req, (string) $actorEmpId);

            $this->auditLog([
                'module'              => 'approval_request',
                'loggable_id'         => (string) $req->form_id,
                'loggable_type'       => ApprovalRequest::class,
                'approval_request_id' => $req->id,
                'actor_employee_id'   => (string) $actorEmpId,
                'actor_role'          => $fromApprover,
                'action'              => 'APPROVE',
                'status_from'         => 'Pending',
                'status_to'           => 'Approved',
                'flow_id'             => $req->approval_flow_id,
                'step_from'           => $fromStep,
                'step_to'             => (int) $req->current_step,
                'approver_from'       => $fromApprover,
                'approver_to'         => null,
                'meta' => [
                    'form_id'      => (string) $formId,
                    'category'     => $req->category,
                    'employee_id'  => $req->employee_id,
                    'approver_id'  => (string) $actorEmpId,
                    'period'       => $req->period,
                    'total_steps'  => $steps->count(),
                    'final'        => true,
                ],
            ]);
            return;
        }

        $next = $steps->firstWhere('step_number', $req->current_step + 1);

        // Konteks: initiator = created_by, target = employee_id
        $initiatorEmpId = (string) $req->initiated->employee_id;
        $targetEmpId    = (string) $req->employee_id;

        $nextApprover = $next
            ? $this->resolveApprover($next, $initiatorEmpId, $targetEmpId, $proposerEmpId = $initiatorEmpId)
            : null;

        $req->update([
            'current_step'        => $req->current_step + 1,
            'current_approval_id' => isset($nextApprover) ? (string) $nextApprover : null,
            'sendback_messages'   => null,
            'updated_by'          => Auth::id(),
        ]);

        $this->auditLog([
            'module'              => 'approval_request',
            'loggable_id'         => (string) $req->form_id,
            'loggable_type'       => ApprovalRequest::class,
            'approval_request_id' => $req->id,
            'actor_employee_id'   => (string) $actorEmpId,
            'actor_role'          => $fromApprover,
            'action'              => 'APPROVE',
            'status_from'         => 'Pending',
            'status_to'           => 'Pending',
            'flow_id'             => $req->approval_flow_id,
            'step_from'           => $fromStep,
            'step_to'             => (int) $req->current_step,
            'approver_from'       => $fromApprover,
            'approver_to'         => (string) $nextApprover,
            'meta' => [
                'form_id'         => (string) $formId,
                'category'        => $req->category,
                'employee_id'     => $req->employee_id, // <-- perbaikan
                'approver_id'     => (string) $actorEmpId,
                'period'          => $req->period,
                'total_steps'     => $steps->count(),
                'next_step_name'  => $next?->step_name,
            ],
        ]);
    }


    public function reject(string $formId, string $actorEmpId, ?string $sendbackTo = null, ?string $message = null): void
    {
        /** @var ApprovalRequest $req */
        $req = ApprovalRequest::where('form_id', $formId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($req->status !== 'Pending') return;

        $isOverride = $this->hasOverridePrivilege();
        if (!$isOverride && !$this->canActOn($req, $actorEmpId)) return;

        // Snapshot (audit)
        $fromStep     = (int) $req->current_step;
        $fromApprover = (string) ($req->current_approval_id ?? null);

        // Normalisasi message & sendbackTo
        $msg = isset($message) && $message !== '' ? mb_substr($message, 0, 1000) : null;
        $sbTo = $sendbackTo; // biarkan sesuai input (role/employee/step-key)

        // Catatan: jika ingin "final reject" saat override, di sini bisa diubah ke status 'Rejected'.
        // Saat ini tetap 'Sendback' agar konsisten flow.
        $req->update([
            'status'              => 'Sendback',
            'current_approval_id' => $req->current_approval_id, // tetap di step ini
            'sendback_to'         => $sbTo,
            'sendback_messages'   => $msg,
            'updated_by'          => Auth::id(),
        ]);

        // Audit trail
        $this->auditLog([
            'module'              => 'approval_request',
            'loggable_id'         => (string) $req->form_id,
            'loggable_type'       => ApprovalRequest::class,
            'approval_request_id' => $req->id,
            'actor_employee_id'   => (string) $actorEmpId,
            'actor_role'          => $fromApprover,   // snapshot sebelum update
            'action'              => $isOverride ? 'SEND_BACK_OVERRIDE' : 'SEND_BACK',
            'status_from'         => 'Pending',
            'status_to'           => 'Sendback',
            'flow_id'             => $req->approval_flow_id,
            'step_from'           => $fromStep,
            'step_to'             => $fromStep,
            'approver_from'       => $fromApprover,
            'approver_to'         => $fromApprover,
            'comments'            => $msg,
            'meta'                => [
                'sendback_to' => $sbTo,
                'override'    => $isOverride ?: false,
                'form_id'     => (string) $formId,
                'category'    => $req->category,
                'employee_id' => $req->employee_id,
                'period'      => $req->period,
            ],
        ]);
    }


    private function hasOverridePrivilege(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // A) Spatie Permission (opsional)
        if ($user->roles()->exists()) {
            return true;
        }

        // B) Flag di roles (dinamis): can_override atau value (truthy)
        if (isset($user->roles)) {
            $hasFlag = $user->roles->contains(function($r){
                return (bool)($r->can_override ?? $r->value ?? false);
            });
            if ($hasFlag) return true;
        }

        // C) Fallback via config (ENV)
        $overrideRoles = (array) config('approval.override_roles', []);
        if (!empty($overrideRoles) && isset($user->roles)) {
            $names = $user->roles->pluck('name')->all();
            return (bool) array_intersect($names, $overrideRoles);
        }

        return false;
    }

    public function resubmit(string $formId, string $actorEmpId, ?string $message = null): void
    {
        DB::transaction(function () use ($formId, $actorEmpId, $message) {

            $req = ApprovalRequest::with(['initiated'])->where('form_id', $formId)->lockForUpdate()->firstOrFail();

            // Ambil flow & step tujuan (sendback_to kalau numerik, else step saat ini)
            $steps = ApprovalFlowStep::where('approval_flow_id', $req->approval_flow_id)
                        ->orderBy('step_number')->get();                        
            $targetStepNo = is_numeric($req->sendback_to) ? (int) $req->sendback_to : (int) $req->current_step;
            
            $step = $steps->firstWhere('step_number', $targetStepNo) ?: $steps->first();
            
            // Recalculate current approver berbasis initiator (created_by) & target (employee_id)
            $initiatorEmpId = (string) $req->initiated->employee_id;
            $targetEmpId    = (string) $req->employee_id;
            
            $nextApprover = $step
            ? $this->resolveApprover($step, $initiatorEmpId, $targetEmpId, $proposerEmpId = $initiatorEmpId)
            : null;

            // Snapshot sebelum update untuk audit
            $before = [
                'status'        => $req->status,
                'step'          => (int) $req->current_step,
                'approver'      => (string) ($req->current_approval_id ?? ''),
            ];

            $req->update([
                'status'              => 'Pending',
                'current_step'        => $step ? (int) $step->step_number : (int) $req->current_step,
                'current_approval_id' => isset($nextApprover) ? (string) $nextApprover : null, // bisa employee_id atau role name
                'messages'            => $message,
                'updated_by'          => Auth::id(),
                'sendback_to'         => null, // clear pointer
            ]);

            // Audit trail
            $this->auditLog([
                'module'              => 'approval_request',
                'action'              => 'RESUBMIT',
                'approval_request_id' => $req->id,
                'actor_employee_id'   => (string) $actorEmpId,
                'flow_id'             => $req->approval_flow_id,
                'loggable_id'         => $req->form_id,
                'loggable_type'       => $req->category,
                'comments'            => $message,
                'status_from'         => $before['status'],
                'status_to'           => 'PENDING',
                'step_from'           => $before['step'],
                'step_to'             => (int) $req->current_step,
                'approver_from'       => $before['approver'],
                'approver_to'         => isset($nextApprover) ? (string) $nextApprover : null,
                'meta'                => ['reason' => 'sendback_revision'],
            ]);
        });
    }


    protected function finalize(ApprovalRequest $req, string $actorEmpId): void
    {
        $map = config('approval.finalizers', [
            // category => [ServiceClass, method]
            'Proposed360' => [Proposed360Service::class, 'applyFromApprovalRequest'],
            // 'Attendance' => [\App\Services\AttendanceService::class, 'applyFromApprovalRequest'],
            // tambahkan modul lain di sini kapan pun
        ]);

        $category = (string) $req->category;
        if (!isset($map[$category])) return;

        [$cls, $method] = $map[$category];

        try {
            app($cls)->{$method}($req, $actorEmpId);
        } catch (\Throwable $e) {
            // jangan gagalkan Approved; opsional: catat error ke approval_logs atau laravel.log
            // \Log::warning("Finalize {$category} gagal: ".$e->getMessage(), ['form_id'=>$req->form_id]);
        }
    }

    protected function canActOn(ApprovalRequest $req, int $actorEmpId): bool
    {
        $cur = (string) ($req->current_approval_id ?? '');
        if ($cur === '') return false;

        // numeric-string → pin ke employee_id
        if (ctype_digit($cur)) return (int)$cur === (int)$actorEmpId;

        // selain numeric → asumsikan nama role
        $user = Auth::user();
        return $this->userHasRoleName($user, $cur);
    }

    protected function userHasRoleName($user, string $roleName): bool
    {
        if (!$user) return false;

        // Spatie (prefer)
        if (class_exists(Role::class) && method_exists($user, 'hasRole')) {
            return $user->hasRole($roleName);
        }

        // Fallback: roles table + pivot umum
        $roleId = DB::table('roles')->where('name',$roleName)->orWhere('code',$roleName)->value('id');
        if (!$roleId) return false;

        foreach (['model_has_roles','role_user','user_roles'] as $pivot) {
            if (!Schema::hasTable($pivot)) continue;
            $exists = DB::table($pivot)->when($pivot==='model_has_roles', function ($q) use ($user,$roleId) {
                $q->where('model_type', get_class($user))->where('model_id', $user->getKey())->where('role_id',$roleId);
            }, function ($q) use ($user,$roleId) {
                $q->where('user_id', $user->getKey())->where('role_id', $roleId);
            })->exists();
            if ($exists) return true;
        }
        return false;
    }


    protected function resolveApprover(
        ApprovalFlowStep $step,
        string $initiatorEmpId,
        string $targetEmpId,
        string $proposerEmpId
    ): ?string {
        $settings = $this->parseSettings($step->settings_json ?? null);
        $context  = $settings['approver_context'] ?? 'initiator'; // 'initiator' | 'target'
        $contextEmpId = $context === 'target' ? $targetEmpId : $initiatorEmpId;

        $emp = EmployeeAppraisal::select('employee_id','manager_l1_id','manager_l2_id')
            ->where('employee_id', $contextEmpId)->first();

        $roles     = $this->decodeToArray($step->approver_role);
        $delegates = $this->decodeToArray($step->approver_user_id);

        foreach ($roles as $raw) {
            $role = $this->normRole($raw);

            // Hirarki berdasarkan konteks → kembalikan employee_id (string)
            if ($role === 'self' && $emp?->employee_id)   return (string) $emp->employee_id;
            if ($role === 'manager_l1_id' && $emp?->manager_l1_id) return (string) $emp->manager_l1_id;
            if ($role === 'manager_l2_id' && $emp?->manager_l2_id) return (string) $emp->manager_l2_id;

            // Proposer → employee_id (string)
            if ($role === 'proposer') return (string) $proposerEmpId;

            // System role → KEMBALIKAN NAMA ROLE (string)
            dd($roles);
            $sysRoleName = $this->canonicalRoleName($role);
            if ($sysRoleName) return $sysRoleName;
        }

        // Delegasi (user id) → employee_id (string)
        foreach ($delegates as $d) {
            $id = trim((string)$d);
            if ($id !== '') return $id;
        }

        // Fallback terakhir
        return (string) $proposerEmpId;
    }

    protected function canonicalRoleName(string $role): ?string
    {
        if (class_exists(Role::class)) {
            $rm = Role::where('name',$role)->orWhere('guard_name',$role)->first();
            if ($rm) return (string) $rm->name;
        }
        $row = DB::table('roles')->where('name',$role)->orWhere('code',$role)->first();
        if ($row) return (string) ($row->name ?? $row->code ?? null);
        return null;
    }

    protected function parseSettings($json): array
    {
        if (is_array($json)) return $json;
        if (is_string($json) && (str_starts_with(trim($json), '{') || str_starts_with(trim($json), '['))) {
            try { return (array) json_decode($json, true) ?: []; } catch (\Throwable $e) { return []; }
        }
        return [];
    }

    protected function decodeToArray($val): array
    {
        if (is_array($val)) return array_values(array_filter($val, fn($v)=>$v!==null && $v!==''));
        if (is_string($val)) {
            $v = trim($val);
            if ($v === '') return [];
            if (str_starts_with($v, '[')) {
                try { $arr = json_decode($v, true); return is_array($arr) ? $arr : []; } catch (\Throwable $e) { return []; }
            }
            return [$v];
        }
        return [];
    }

    protected function normRole(string $raw): string
    {
        $k = strtolower(trim($raw));
        return match ($k) {
            'l1 manager','l1_manager','manager l1','l1_manager_id','manager_l1','manager_l1_id' => 'manager_l1_id',
            'l2 manager','l2_manager','manager l2','l2_manager_id','manager_l2','manager_l2_id' => 'manager_l2_id',
            'self' => 'self',
            'proposer','requester','initiator' => 'proposer',
            default => $k,
        };
    }

    protected function findApproverBySystemRole(string $role, string $targetEmployeeId, string $proposerId)
    {
        // 1) Spatie (jika ada)
        if (class_exists(Role::class)) {
            $roleModel = Role::where('name',$role)->orWhere('guard_name',$role)->first();
            if ($roleModel) {
                $pivot = DB::table('model_has_roles')->where('role_id',$roleModel->id)->first();
                if ($pivot) {
                    $userClass = $pivot->model_type ?? null;
                    if ($userClass && class_exists($userClass)) {
                        $user = $userClass::find($pivot->model_id);
                        if ($user && property_exists($user,'employee_id')) return $user->employee_id;
                    }
                }
            }
        }

        // 2) Fallback tabel roles umum
        $roleId = DB::table('roles')->where('code',$role)->orWhere('name',$role)->value('id');
        if ($roleId) {
            foreach (['role_user','user_roles'] as $pivot) {
                if (Schema::hasTable($pivot)) {
                    $uid = DB::table($pivot)->where('role_id',$roleId)->value('user_id');
                    if ($uid) {
                        $empId = DB::table('users')->where('id',$uid)->value('employee_id');
                        if ($empId) return $empId;
                    }
                }
            }
        }

        return null;
    }

    private function auditLog(array $payload, bool $alsoToDb = true): void
    {
        // 2.1 tulis ke file log (JSON)
        Log::channel('audit')->info('AUDIT', $payload);

        // 2.2 (opsional) tetap simpan ke DB approval_logs yg sudah kamu miliki
        if (!$alsoToDb) return;

        DB::table('approval_logs')->insert([
            'approval_request_id' => $payload['approval_request_id'] ?? null,
            'actor_employee_id'   => (string)($payload['actor_employee_id'] ?? ''),
            'action'              => strtoupper($payload['action'] ?? 'UNKNOWN'),
            'comments'            => $payload['comments'] ?? null,
            'acted_at'            => now(),
            'module'              => $payload['module'] ?? 'Proposed360',
            'loggable_id'         => $payload['loggable_id'] ?? null,
            'loggable_type'       => $payload['loggable_type'] ?? null,
            'flow_id'             => $payload['flow_id'] ?? null,
            'step_from'           => $payload['step_from'] ?? null,
            'step_to'             => $payload['step_to'] ?? null,
            'status_from'         => $payload['status_from'] ?? null,
            'status_to'           => $payload['status_to'] ?? null,
            'approver_from'       => $payload['approver_from'] ?? null,
            'approver_to'         => $payload['approver_to'] ?? null,
            'actor_role'          => $payload['actor_role'] ?? null,
            'meta_json'           => empty($payload['meta']) ? null : json_encode($payload['meta']),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
