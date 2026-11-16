<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SendbackController extends Controller
{
    public function store(Request $request)

    {
        $checkRequest = ApprovalRequest::with(['approval'])->find($request->request_id);

        $firstApproval = $checkRequest->approval()
        ->orderBy('id') // Urutkan berdasarkan ID (secara default akan urutkan dari yang terkecil)
        ->first();
        
        if ($checkRequest->employee_id == $request->sendto) {
            $approval = Approval::where('request_id', $request->request_id);
            $approval->delete();
            if ($firstApproval) {
                $sendto = $firstApproval->approver_id;
            }else{
                $sendto = $checkRequest->current_approval_id;
            }
        }else{
            $approval_id = Approval::where('request_id', $request->request_id)->where('approver_id', $request->sendto)->value('id');

            $approver_ids = Approval::where('request_id', $request->request_id)->where('id', '>=', $approval_id)->pluck('approver_id')->toArray();

            $approval = Approval::where('id', '>=', $approval_id)->where('request_id', $request->request_id);
            $approval->delete();

            $approvalSnapshot = ApprovalSnapshots::whereIn('employee_id', $approver_ids)->where('form_id', $request->form_id);
            $approvalSnapshot->delete();
            $sendto = $request->sendto;
        }


        $model = ApprovalRequest::find($request->request_id);
        $model->current_approval_id = $sendto;
        $model->sendback_to = $request->sendto;
        $model->status = $request->sendback;
        $model->sendback_messages = $request->sendback_message;
        $model->updated_by = Auth::user()->id;
        
        $model->save();

        // Kirim respons JSON ke JavaScript
        return redirect()->route('onbehalf', ['category' => 'Goals']);

    }
}
