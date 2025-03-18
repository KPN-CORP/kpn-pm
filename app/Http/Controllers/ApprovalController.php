<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ApprovalController extends Controller
{

    public function store(Request $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Determine next approver and status
            $currentLayer = ApprovalLayer::where('approver_id', $request->current_approver_id)
                ->where('employee_id', $request->employee_id)
                ->value('layer');

            $nextLayer = $currentLayer ? $currentLayer + 1 : 1;
            $nextApprover = ApprovalLayer::where('employee_id', $request->employee_id)
                ->where('layer', $nextLayer)
                ->value('approver_id');

            $statusRequest = $nextApprover ? 'Pending' : 'Approved';
            $statusForm = $nextApprover ? 'Submitted' : 'Approved';

            // Validate form submission
            if ($request->submit_type === 'submit_form') {
                $rules = [
                    'kpi.*' => 'required|string',
                    'target.*' => 'required|string',
                    'uom.*' => 'required|string',
                    'weightage.*' => 'required|integer|min:5|max:100',
                    'type.*' => 'required|string',
                ];

                $validator = Validator::make($request->all(), $rules, [
                    'weightage.*.integer' => 'Weightage harus berupa angka.',
                    'weightage.*.min' => 'Weightage minimal :min%.',
                    'weightage.*.max' => 'Weightage maksimal :max%.',
                ]);

                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }
            }

            // Prepare KPI data
            $kpiData = [];
            foreach ($request->input('kpi', []) as $index => $kpi) {
                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'target' => $request->target[$index],
                    'uom' => $request->uom[$index],
                    'weightage' => $request->weightage[$index],
                    'type' => $request->type[$index],
                    'custom_uom' => $request->custom_uom[$index] ?? null,
                ];
            }

            $jsonData = json_encode($kpiData);

            // Update approval snapshot
            $snapshot = ApprovalSnapshots::firstOrNew([
                'form_id' => $request->id,
                'employee_id' => $request->current_approver_id,
            ]);

            $snapshot->form_data = $jsonData;
            $snapshot->updated_by = Auth::id();
            $snapshot->created_by = $snapshot->created_by ?? Auth::id();

            if (!$snapshot->save()) {
                throw new Exception('Gagal menyimpan snapshot persetujuan');
            }

            // Update goal status
            $goal = Goal::findOrFail($request->id);
            $goal->form_status = $statusForm;

            if (!$goal->save()) {
                throw new Exception('Gagal memperbarui status goal');
            }

            // Update approval request
            $approvalRequest = ApprovalRequest::where('form_id', $request->id)
                ->firstOrFail();
                
            $approvalRequest->current_approval_id = $nextApprover ?? $request->current_approver_id;
            $approvalRequest->status = $statusRequest;
            $approvalRequest->updated_by = Auth::id();
            $approvalRequest->messages = $request->messages;
            $approvalRequest->sendback_messages = null;
            $approvalRequest->sendback_to = null;

            if (!$approvalRequest->save()) {
                throw new Exception('Gagal memperbarui permintaan persetujuan');
            }

            // Update/create approval record
            $approval = Approval::firstOrNew([
                'request_id' => $approvalRequest->id,
                'approver_id' => $request->current_approver_id,
            ]);

            $approval->messages = $request->messages;
            $approval->status = $statusRequest === 'Approved' ? 'Approved' : 'Processed';
            $approval->created_by = Auth::id();

            if (!$approval->save()) {
                throw new Exception('Gagal menyimpan catatan persetujuan');
            }

            DB::commit();
            return redirect()->route('team-goals')->with('success', 'Data berhasil disimpan');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approval process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => Auth::id(),
                'goal_id' => $request->id ?? 'N/A',
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }
}
