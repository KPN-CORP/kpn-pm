<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\EmployeeAppraisal;
use App\Models\Proposed360;
use App\Services\ApprovalEngine;
use App\Services\AppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminTasksController extends Controller
{
    protected $user;
    protected $appService;
    protected $period;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->user = Auth::user()->employee_id;
        $this->period = $this->appService->appraisalPeriod();
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $roles  = $this->getUserRoleNames($user);           // ['Admin', 'HRBP', ...]
        if (empty($roles)) {
            return view('pages.admin-task.app', ['tasks' => collect(), 'empMap' => collect(), 'roleCandidates' => collect()])
                ->with('error','Anda tidak memiliki role untuk task approval.');
        }

        // Ambil task PENDING yg ditugaskan ke salah satu role user
        $tasks = ApprovalRequest::query()
            ->select('id','form_id','employee_id','category','period','current_step','total_steps','current_approval_id','status','created_at')
            ->where('status','Pending')
            ->whereIn('current_approval_id', $roles)        // current_approval_id = role name
            ->orderByDesc('created_at')
            ->paginate(15);

        // Map employee label
        $empIds = $tasks->pluck('employee_id')->filter()->unique()->values()->all();
        $empMap = EmployeeAppraisal::select('employee_id','fullname','designation_name')
            ->whereIn('employee_id', $empIds)->get()->keyBy('employee_id');

        // Kumpulkan role yg tampil di halaman → resolusi kandidat untuk tooltip/list
        $roleNamesUsed = $tasks->pluck('current_approval_id')->filter()->unique()->values()->all();
        $roleCandidates = $this->getRoleCandidatesLabels($roleNamesUsed); // ['HRBP'=>['Nama (123)'], ...]

        $parentLink = __('Admin Tasks');
        $link = __('Tasks');

        return view('pages.admin-task.app', compact('parentLink','link','tasks','empMap','roleCandidates'));
    }

    public function detail(string $id)
    {
        $user  = Auth::user();
        $roles = $this->getUserRoleNames($user);

        $req = ApprovalRequest::select('*')->findOrFail($id);
        if ($req->status !== 'Pending') {
            abort(403, 'Task tidak dalam status Pending.');
        }
        if (!in_array($req->current_approval_id, $roles, true)) {
            abort(403, 'Anda tidak memiliki role untuk task ini.');
        }

        $employee = EmployeeAppraisal::select('employee_id','fullname','designation_name','manager_l1_id','manager_l2_id')
            ->where('employee_id', $req->employee_id)->first();

        $initiator = EmployeeAppraisal::select('employee_id','fullname')
            ->where('id', $req->created_by)->first();

        $formDetail = null;
        if ($req->category === 'Proposed360') {
            $formDetail = Proposed360::select('id','scope','peers','subordinates','managers','notes','appraisal_year','proposer_employee_id','employee_id')
                ->find($req->form_id);
            if ($formDetail) {
                $formDetail->peers         = is_string($formDetail->peers) ? json_decode($formDetail->peers, true) : ($formDetail->peers ?? []);
                $formDetail->subordinates  = is_string($formDetail->subordinates) ? json_decode($formDetail->subordinates, true) : ($formDetail->subordinates ?? []);
                $formDetail->managers      = is_string($formDetail->managers) ? json_decode($formDetail->managers, true) : ($formDetail->managers ?? []);
            }
        }

        $parentLink = __('Tasks');
        $link = __('Approval Details');

        // Kandidat untuk role ini (tooltip/list)
        $candidates = $this->getRoleCandidatesLabels([$req->current_approval_id])[$req->current_approval_id] ?? [];

        return view('pages.admin-task.show', compact('parentLink','link','req','employee','initiator','formDetail','candidates'));
    }

    public function action(string $id, Request $request, ApprovalEngine $engine)
    {
        $request->validate([
            'action'  => 'required|in:APPROVE,REJECT',
            'message' => 'nullable|string|max:1000',
        ]);

        $user         = Auth::user();
        $actorEmpId   = (string) ($user->employee_id ?? '');
        $roles        = $this->getUserRoleNames($user);

        /** @var ApprovalRequest $req */
        $req = ApprovalRequest::lockForUpdate()->findOrFail($id);
        if ($req->status !== 'Pending') {
            return back()->with('error','Task tidak dalam status Pending.');
        }
        if (!in_array($req->current_approval_id, $roles, true)) {
            return back()->with('error','Anda tidak memiliki role untuk task ini.');
        }

        try {
            DB::beginTransaction();
            if ($request->action === 'APPROVE') {
                $engine->approve((string)$req->form_id, $actorEmpId);
                DB::commit();
                return redirect()->route('admin-tasks')->with('success','Task Approved Succesfully');
            }
            // REJECT
            $engine->reject((string)$req->form_id, $actorEmpId, null, $request->message);
            DB::commit();
            return redirect()->route('admin-tasks')->with('success','Task has been Sendbacked.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error','Gagal memproses task: '.$e->getMessage());
        }
    }

    /** ---------- Helpers ---------- */

    // Ambil semua nama role milik user (Spatie > fallback)
    protected function getUserRoleNames($user): array
    {
        if (!$user) return [];
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->toArray();
        }

        $names = [];
        if (Schema::hasTable('model_has_roles')) {
            $rows = DB::table('model_has_roles')->where('model_type', get_class($user))->where('model_id',$user->getKey())->pluck('role_id');
            if ($rows->isNotEmpty() && Schema::hasTable('roles')) {
                $names = array_merge($names, DB::table('roles')->whereIn('id',$rows)->pluck('name')->toArray());
            }
        }
        foreach (['role_user','user_roles'] as $pivot) {
            if (!Schema::hasTable($pivot) || !Schema::hasTable('roles')) continue;
            $rids = DB::table($pivot)->where('user_id',$user->getKey())->pluck('role_id');
            if ($rids->isNotEmpty()) {
                $names = array_merge($names, DB::table('roles')->whereIn('id',$rids)->pluck('name')->toArray());
            }
        }
        return array_values(array_unique(array_filter($names)));
    }

    // Map: role name → ["Fullname (employee_id)", ...]
    protected function getRoleCandidatesLabels(array $roleNames): array
    {
        $roleNames = array_values(array_unique(array_filter($roleNames)));
        if (empty($roleNames)) return [];

        // Spatie
        $map = [];
        if (class_exists(\Spatie\Permission\Models\Role::class) && Schema::hasTable('model_has_roles')) {
            $roleModels = \Spatie\Permission\Models\Role::whereIn('name',$roleNames)->get(['id','name']);
            $rows = DB::table('model_has_roles')->whereIn('role_id',$roleModels->pluck('id'))->get();
            $users = DB::table('users')->whereIn('id', $rows->pluck('model_id')->unique())->pluck('employee_id','id');
            $empIds = array_values(array_unique(array_filter($users->values()->all())));
            $empMap = EmployeeAppraisal::select('employee_id','fullname')->whereIn('employee_id',$empIds)->get()->keyBy('employee_id');

            foreach ($roleModels as $rm) {
                $uids = $rows->where('role_id',$rm->id)->pluck('model_id')->unique()->values();
                $labels = [];
                foreach ($uids as $uid) {
                    $eid = (string)($users[$uid] ?? '');
                    if ($eid === '') continue;
                    $labels[] = ($empMap[$eid]->fullname ?? $eid).' ('.$eid.')';
                }
                $map[$rm->name] = $labels;
            }
        }

        // Fallback pivot umum
        if (empty($map) && Schema::hasTable('roles')) {
            $roles = DB::table('roles')->whereIn('name',$roleNames)->get(['id','name']);
            $roleIdByName = $roles->pluck('id','name');
            $all = [];
            foreach (['role_user','user_roles'] as $pivot) {
                if (!Schema::hasTable($pivot)) continue;
                $rows = DB::table($pivot)->whereIn('role_id',$roleIdByName->values())->get(['role_id','user_id']);
                foreach ($rows as $r) $all[$r->role_id][] = $r->user_id;
            }
            $userIds = array_values(array_unique(array_merge(...array_values($all ?: []))));
            $users = DB::table('users')->whereIn('id',$userIds)->pluck('employee_id','id');
            $empIds = array_values(array_unique(array_filter($users->values()->all())));
            $empMap = EmployeeAppraisal::select('employee_id','fullname')->whereIn('employee_id',$empIds)->get()->keyBy('employee_id');

            foreach ($roleIdByName as $name => $rid) {
                $uids = array_unique($all[$rid] ?? []);
                $labels = [];
                foreach ($uids as $uid) {
                    $eid = (string)($users[$uid] ?? '');
                    if ($eid === '') continue;
                    $labels[] = ($empMap[$eid]->fullname ?? $eid).' ('.$eid.')';
                }
                $map[$name] = $labels;
            }
        }

        return $map;
    }
}
