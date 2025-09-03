<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovalFlowRequest;
use App\Http\Requests\UpdateApprovalFlowRequest;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Assignment;
use App\Models\Employee;
use App\Models\Role; // Pastikan Anda memiliki model Role atau sesuaikan
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Digunakan untuk created_by
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ApprovalFlowController extends Controller
{
    /**
     * Menampilkan daftar alur persetujuan yang ada.
     * Menggabungkan logika dari indexApprovalFlows dan indexFlows.
     */
    public function index()
    {
        $flows = ApprovalFlow::with('steps')->latest()->get();

        $parentLink = __('Flow Builder');
        $link = __('Approval Flows');

        // Menggunakan path view yang konsisten
        return view('pages.approval-flow.app', compact('flows', 'parentLink', 'link'));
    }

    public function data()
    {
        $flows = ApprovalFlow::select(['id', 'flow_name', 'description', 'is_active', 'created_at']);

        return DataTables::of($flows)
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y');
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? 'Yes' : 'No';
            })
            ->addColumn('action', function ($flow) {
                return view('components.flow-table-action-buttons', [
                    'editRoute'   => 'approval-flow.edit',
                    'deleteRoute' => 'approval-flow.destroy',
                    'id'          => $flow->id,
                    'type'        => 'default'
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    /**
     * Menampilkan form untuk membuat alur persetujuan baru.
     * Disesuaikan agar cocok dengan create.blade.php.
     */
    public function create()
    {
        // $approverRoles = Role::where('name', '!=', 'superadmin')
        //     ->pluck('name', 'id')
        //     ->toArray();

        $approverRoles = Role::pluck('name', 'id')->toArray();

        $approverRoles = [
            'L1' => 'L1 Manager',
            'L2' => 'L2 Manager',
            'L3' => 'L3 Manager',
        ] + $approverRoles;


        $employees = Employee::select('employee_id', DB::raw("CONCAT(fullname, ' (', employee_id, ')') AS name"))
            ->orderBy('fullname')
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->employee_id,
                    'value' => $item->name
                ];
            })
            ->values(); // Reset indeks numerik

        // Data dummy atau dari database untuk opsi di modal "Additional Settings"
        $formVisibilityOptions = [
            'Self' => 'Self', 'Manager' => 'Manager', 'L2 Manager' => 'L2 Manager',
            'Head HC' => 'Head HC', 'Director Unit' => 'Director Unit', 'CEO Business' => 'CEO Business'
        ];

        $parentLink = __('Approval Flows');
        $link = __('Create');

        // Menggunakan path view dari file blade yang diberikan
        return view('pages.approval-flow.create', compact(
            'parentLink',
            'link',
            'approverRoles',
            'employees',
            'formVisibilityOptions'
        ));
    }

    /**
     * Menyimpan alur persetujuan baru ke database.
     * Logika ini sepenuhnya ditulis ulang agar sesuai dengan struktur form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreApprovalFlowRequest $request)
    {
        // Validasi sudah dilakukan oleh StoreApprovalFlowRequest

        $flow = ApprovalFlow::create([
            'flow_name' => $request->flow_name,
            // 'module_transaction_type' tidak ada di form final yang diberikan, jadi tidak disimpan di sini.
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
            'settings_json' => isset($stepData['settings_json']) ? json_decode($stepData['settings_json'], true) : null,
            'created_by' => Auth::id(),
        ]);

        // Simpan langkah-langkah alur
        foreach ($request->steps as $stepData) {
            $flow->steps()->create([
                'step_number' => $stepData['step_number'],
                // approver_role_or_user_id akan menyimpan array JSON dari peran yang dipilih
                // Pastikan ini adalah array, bahkan jika kosong
                'approver_role' => $stepData['approver_role'] ?? [],
                // approver_user_id akan menyimpan array JSON dari user ID yang dipilih
                // Pastikan ini adalah array, bahkan jika kosong
                'approver_user_id' => $stepData['approver_user_id'] ?? [],
                'step_name' => $stepData['step_name'] ?? null,
                // 'required_action' tidak ada di form final yang diberikan, jadi tidak disimpan di sini.
                'allotted_time' => $stepData['allotted_time'] ?? null,
            ]);
        }

        return redirect()->route('approval-flow.index')->with('success', 'Approval flow successfully created');
    }

    public function edit($id)
    {
        $flow = ApprovalFlow::with('steps')->findOrFail($id);

        $approverRoles = Role::pluck('name', 'id')->toArray();
        $approverRoles = [
            'L1' => 'L1 Manager',
            'L2' => 'L2 Manager',
            'L3' => 'L3 Manager',
        ] + $approverRoles;

        $employees = Employee::select('employee_id', DB::raw("CONCAT(fullname, ' (', employee_id, ')') AS name"))
            ->orderBy('fullname')
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->employee_id,
                    'value' => $item->name
                ];
            })
            ->values();

        $formVisibilityOptions = [
            'Self' => 'Self', 'Manager' => 'Manager', 'L2 Manager' => 'L2 Manager',
            'Head HC' => 'Head HC', 'Director Unit' => 'Director Unit', 'CEO Business' => 'CEO Business'
        ];

        $parentLink = __('Approval Flows');
        $link = __('Edit');

        return view('pages.approval-flow.edit', compact(
            'parentLink',
            'link',
            'approverRoles',
            'employees',
            'formVisibilityOptions',
            'flow'
        ));
    }


    public function update(UpdateApprovalFlowRequest $request, $id)
    {
        $flow = ApprovalFlow::findOrFail($id);

        $flow->update([
            'flow_name' => $request->flow_name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
            'settings_json' => isset($request->settings_json) ? json_decode($request->settings_json, true) : null,
            'updated_by' => Auth::id(),
        ]);

        // Hapus semua step lama
        $flow->steps()->delete();

        // Insert ulang step dari request
        foreach ($request->steps as $stepData) {
            $flow->steps()->create([
                'step_number' => $stepData['step_number'],
                'approver_role' => $stepData['approver_role'] ?? [],
                'approver_user_id' => $stepData['approver_user_id'] ?? [],
                'step_name' => $stepData['step_name'] ?? null,
                'allotted_time' => $stepData['allotted_time'] ?? null,
            ]);
        }

        return redirect()->route('approval-flow.index')->with('success', 'Approval flow successfully updated');
    }


    public function destroy(Request $request)
    {
        $approval = ApprovalFlow::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $approval->updated_by = Auth::id();
            $approval->save();

            $approval->delete();

            if($approval){
                ApprovalFlowStep::where('approval_flow_id', $request->id)->delete();
            }

            DB::commit();

            Log::info('Approval Flow successfully deleted', [
                'table' => 'approval_flows',
                'id' => $request->id,
                'deleted_by' => Auth::id(),
            ]);

            return redirect()
                ->route('approval-flow.index')
                ->with('success', 'Approval Flow deleted');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete Approval Flow: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while deleting Approval Flow.');
        }
    }
}
