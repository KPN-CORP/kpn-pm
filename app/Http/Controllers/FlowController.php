<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovalFlowRequest;
use App\Models\ApprovalFlow;
use App\Models\Assignment;
use App\Models\Flow;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FlowController extends Controller
{

    public function index()
    {
        $flows = Flow::all(); // Ambil semua alur persetujuan

        // dd($flows->first()->initiator);

        $parentLink = __('Flow Builder');
        $link = __('Flows');

        return view('pages.flows.app', compact('flows', 'parentLink', 'link'));
    }

    public function data()
    {
        $flows = Flow::select(['id', 'flow_name', 'description', 'created_at']);

        return DataTables::of($flows)
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y');
            })
            ->addColumn('action', function ($flow) {
                return view('components.flow-table-action-buttons', [
                    'editRoute'   => 'flows.edit',
                    'deleteRoute' => 'flows.destroy',
                    'id'          => $flow->id,
                    'type'        => 'default'
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Menampilkan form untuk membuat alur persetujuan baru.
     */
    public function create()
    {
        $moduleTransactions = [
            'Propose 360' => 'Propose 360',
        ];

        // Data dummy untuk dropdown "Approvers" (Roles/Groups)
        $approverRoles = Role::where('name', '!=', 'superadmin')->pluck('name', 'id')->toArray();

        $approverRoles = [
            // 'Self' => 'Self',
            // 'superadmin' => 'Admin',
            'manager_l1_id' => 'L1 Manager',
            'manager_l2_id' => 'L2 Manager',
            // 'l3_manager_id' => 'L3 Manager',
        ] + $approverRoles;

        $assignments = Assignment::select('id', 'name')->pluck('name', 'id');

        $approvalFlow = ApprovalFlow::select('id', 'flow_name')->pluck('flow_name', 'id');

        // Data dummy untuk dropdown "Additional Settings"
        $formVisibilityOptions = [
            'Self' => 'Self', 'Manager' => 'Manager', 'L2 Manager' => 'L2 Manager',
            'Head HC' => 'Head HC', 'Director Unit' => 'Director Unit', 'CEO Business' => 'CEO Business'
        ];
        $approverContextOptions = ['Subject' => 'Subject', 'Source' => 'Source'];
        $assigneeContextOptions = ['Subject' => 'Subject', 'Source' => 'Source'];
        // ... tambahkan opsi untuk dropdown lain sesuai kebutuhan

        $parentLink = __('Flows');
        $link = __('Create');

        return view('pages.flows.create', compact(
            'parentLink', 
            'link', 
            'moduleTransactions',
            'approverRoles',
            'assignments',
            'approvalFlow',
            'formVisibilityOptions',
            'approverContextOptions',
            'assigneeContextOptions'
        ));
    }

    /**
     * Menyimpan alur persetujuan baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_transaction'              => 'required',
            'flow_name'                        => 'required|string|max:255',
            'description'                      => 'nullable|string',
            'assignments'                      => 'required|array|min:1',
            'assignments.*'                    => 'exists:assignments,id',
            'initiator'                         => 'required|array|min:1',
            'initiator.*.role'         => 'required',
            'initiator.*.approval_flow'     => 'required|exists:approval_flows,id',
        ]);

        try {
            // Add step_number automatically
            $steps = [];
            foreach ($validated['initiator'] as $index => $step) {
                [$type, $id, $label] = explode('|', $step['role']);
                [$flowId, $flowName] = explode('|', $step['approval_flow']);
                
                $stepData = [
                    'type'               => $type,
                    'approval_flow_id'   => $flowId,
                    'approval_flow_name' => $flowName,
                ];

                if ($type === 'role') {
                    $stepData['role_id']   = $id;
                    $stepData['role_name'] = $label;
                } elseif ($type === 'state') {
                    $stepData['state_key']   = $id;
                    $stepData['state_label'] = $label;
                }

                $steps[] = $stepData;

            }

            Flow::create([
                'module_transaction' => $validated['module_transaction'],
                'flow_name'              => $validated['flow_name'],
                'description'            => $validated['description'] ?? null,
                'assignments'            => json_encode($validated['assignments']),
                'initiator'               => json_encode($steps),
            ]);

            return redirect()
                ->route('flows.index')
                ->with('success', 'Flow has been created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'An error occurred while creating the flow: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $flow = Flow::findOrFail($id);

        $moduleTransactions = [
            'Propose 360' => 'Propose 360',
        ];

        $approverRoles = Role::pluck('name', 'id')->toArray();
        $approverRoles = [
            'self' => 'Self',
            'manager_l1_id' => 'L1 Manager',
            'manager_l2_id' => 'L2 Manager',
        ] + $approverRoles;

        $assignments = Assignment::select('id', 'name')->pluck('name', 'id');
        $approvalFlow = ApprovalFlow::select('id', 'flow_name')->pluck('flow_name', 'id');

        $formVisibilityOptions = [
            'Self' => 'Self', 'Manager' => 'Manager', 'L2 Manager' => 'L2 Manager',
            'Head HC' => 'Head HC', 'Director Unit' => 'Director Unit', 'CEO Business' => 'CEO Business'
        ];
        $approverContextOptions = ['Subject' => 'Subject', 'Source' => 'Source'];
        $assigneeContextOptions = ['Subject' => 'Subject', 'Source' => 'Source'];

        $parentLink = __('Flows');
        $link = __('Edit');

        // decode JSON fields untuk isi form
        $flow->assignments = json_decode($flow->assignments, true) ?? [];
        $flow->initiator = json_decode($flow->initiator, true) ?? [];

        return view('pages.flows.edit', compact(
            'flow',
            'parentLink',
            'link',
            'moduleTransactions',
            'approverRoles',
            'assignments',
            'approvalFlow',
            'formVisibilityOptions',
            'approverContextOptions',
            'assigneeContextOptions'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'module_transaction'         => 'required',
            'flow_name'                   => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'assignments'                 => 'required|array|min:1',
            'assignments.*'               => 'exists:assignments,id',
            'initiator'                    => 'required|array|min:1',
            'initiator.*.role'             => 'required',
            'initiator.*.approval_flow'    => 'required|exists:approval_flows,id',
        ]);

        try {
            $steps = [];
            foreach ($validated['initiator'] as $step) {
                [$type, $idValue, $label] = explode('|', $step['role']);
                [$flowId, $flowName] = explode('|', $step['approval_flow']);

                $stepData = [
                    'type'               => $type,
                    'approval_flow_id'   => $flowId,
                    'approval_flow_name' => $flowName,
                ];

                if ($type === 'role') {
                    $stepData['role_id']   = $idValue;
                    $stepData['role_name'] = $label;
                } elseif ($type === 'state') {
                    $stepData['state_key']   = $idValue;
                    $stepData['state_label'] = $label;
                }

                $steps[] = $stepData;
            }

            $flow = Flow::findOrFail($id);
            $flow->update([
                'module_transaction' => $validated['module_transaction'],
                'flow_name'           => $validated['flow_name'],
                'description'         => $validated['description'] ?? null,
                'assignments'         => json_encode($validated['assignments']),
                'initiator'           => json_encode($steps),
            ]);

            return redirect()
                ->route('flows.index')
                ->with('success', 'Flow has been updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'An error occurred while updating the flow: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $flow = Flow::findOrFail($id);
            $flow->delete();

            return redirect()
                ->route('flows.index')
                ->with('success', 'Flow has been deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'An error occurred while deleting the flow: ' . $e->getMessage());
        }
    }


}
