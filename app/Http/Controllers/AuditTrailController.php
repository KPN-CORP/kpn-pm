<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLog;
use App\Models\EmployeeAppraisal;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 50);

        $q = ApprovalLog::select(
                'id',
                'module',
                'loggable_id',
                'loggable_type',
                'approval_request_id',
                'flow_id',
                'step_from','step_to',
                'status_from','status_to',
                'approver_from','approver_to',
                'actor_employee_id','actor_role',
                'action','comments','meta_json',
                'acted_at'
            );

        // Filters
        if ($m = $request->input('module')) {
            $q->where('module', $m);
        }
        if ($a = $request->input('action')) {
            $q->where('action', strtoupper($a));
        }
        if ($from = $request->input('from')) {
            $q->where('acted_at', '>=', $from.' 00:00:00');
        }
        if ($to = $request->input('to')) {
            $q->where('acted_at', '<=', $to.' 23:59:59');
        }
        if ($s = $request->input('search')) {
            $q->where(function($w) use ($s) {
                $w->where('actor_employee_id','like',"%{$s}%")
                  ->orWhere('actor_role','like',"%{$s}%")
                  ->orWhere('approver_from','like',"%{$s}%")
                  ->orWhere('approver_to','like',"%{$s}%")
                  ->orWhere('status_from','like',"%{$s}%")
                  ->orWhere('status_to','like',"%{$s}%")
                  ->orWhere('module','like',"%{$s}%")
                  ->orWhere('loggable_id','like',"%{$s}%")
                  ->orWhere('comments','like',"%{$s}%");
            });
        }

        $logs = $q->orderByDesc('acted_at')
                  ->paginate($perPage)
                  ->appends($request->query());

        // Dropdown data
        $modules = ApprovalLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ApprovalLog::select('action')->distinct()->orderBy('action')->pluck('action');

        // Map actor name (opsional)
        $actorIds = collect($logs->items())->pluck('actor_employee_id')->filter()->unique();
        $empMap = EmployeeAppraisal::select('employee_id','fullname')
            ->whereIn('employee_id', $actorIds)
            ->pluck('fullname','employee_id');

        $parentLink = __('Audit Trail');
        $link = __('Logs');

        return view('pages.audit-trail.app', compact('parentLink','link','logs','modules','actions','empMap'));
    }
}
