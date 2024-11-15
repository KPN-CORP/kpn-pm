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
use Illuminate\Support\Str;

class ApprovalController extends Controller
{

    public function store(Request $request): RedirectResponse

    {
        // Inisialisasi array untuk menyimpan pesan validasi kustom

        $nextLayer = ApprovalLayer::where('approver_id', $request->current_approver_id)
                                    ->where('employee_id', $request->employee_id)->max('layer');

        // Cari approver_id pada layer selanjutnya
        $nextApprover = ApprovalLayer::where('layer', $nextLayer + 1)->where('employee_id', $request->employee_id)->value('approver_id');

        if (!$nextApprover) {
            $approver = $request->current_approver_id;
            $statusRequest = 'Approved';
            $statusForm = 'Approved';
        }else{
            $approver = $nextApprover;
            $statusRequest = 'Pending';
            $statusForm = 'Submitted';
        }

        $status = 'Approved';

        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $custom_uoms = $request->input('custom_uom', []);

        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|string',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|integer|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.integer' => 'Weightage harus berupa angka.',
            'weightage.*.min' => 'Weightage harus lebih besar atau sama dengan :min %.',
            'weightage.*.max' => 'Weightage harus kurang dari atau sama dengan :max %.',
        ];

        // Membuat Validator instance
        if ($request->submit_type === 'submit_form') {
            $validator = Validator::make($request->all(), $rules, $customMessages);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        // Inisialisasi array untuk menyimpan data KPI
        
        $kpiData = [];
        // Reset nomor indeks untuk penggunaan berikutnya
        $index = 1;

        // Iterasi melalui input untuk mendapatkan data KPI
        foreach ($kpis as $index => $kpi) {
            // Memastikan ada nilai untuk semua input terkait
            if (isset($targets[$index], $uoms[$index], $weightages[$index], $types[$index])) {
                // Simpan data KPI ke dalam array dengan nomor indeks sebagai kunci
                if($custom_uoms){
                    $customuom = $custom_uoms[$index];
                }else{
                    $customuom = null;
                }

                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'target' => $targets[$index],
                    'uom' => $uoms[$index],
                    'weightage' => $weightages[$index],
                    'type' => $types[$index],
                    'custom_uom' => $customuom
                ];

                $index++;
            }
        }
        // Simpan data KPI ke dalam file JSON
        $jsonData = json_encode($kpiData);

        $checkApprovalSnapshots = ApprovalSnapshots::where('form_id', $request->id)->where('employee_id', $request->current_approver_id)->first();

        if ($checkApprovalSnapshots) {
            $snapshot = ApprovalSnapshots::find($checkApprovalSnapshots->id);
            $snapshot->form_data = $jsonData;
            $snapshot->updated_by = Auth::user()->id;
        } else {
            $snapshot = new ApprovalSnapshots;
            $snapshot->id = Str::uuid();
            $snapshot->form_data = $jsonData;
            $snapshot->form_id = $request->id;
            $snapshot->employee_id = Auth::user()->employee_id;
            $snapshot->created_by = Auth::user()->id;

        }
        $snapshot->save();

        $model = Goal::find($request->id);
        $model->form_data = $jsonData;
        $model->form_status = $statusForm;
        
        $model->save();

        $approvalRequest = ApprovalRequest::where('form_id', $request->id)->first();
        $approvalRequest->current_approval_id = $approver;
        $approvalRequest->status = $statusRequest;
        $approvalRequest->updated_by = Auth::user()->id;
        $approvalRequest->messages = $request->messages;
        $approvalRequest->sendback_messages = null;
        $approvalRequest->sendback_to = null;
        // Set other attributes as needed
        $approvalRequest->save();

        $checkApproval = Approval::where('request_id', $approvalRequest->id)->where('approver_id', $request->current_approver_id)->first();

        if ($checkApproval) {
            $approval = $checkApproval;
            $approval->messages = $request->messages;

        } else {
            $approval = new Approval;
            $approval->request_id = $approvalRequest->id;
            $approval->approver_id = Auth::user()->employee_id;
            $approval->created_by = Auth::user()->id;
            $approval->status = $status;
            $approval->messages = $request->messages;
            // Set other attributes as needed
        }
        $approval->save();
            
        return redirect()->route('team-goals');
    }
}
