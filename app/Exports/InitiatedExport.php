<?php

namespace App\Exports;

use App\Models\ApprovalLayer;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;

class InitiatedExport implements FromView, WithStyles
{
    use Exportable;

    protected $employeeId;

    public function __construct($employeeId)
    {
        $this->employeeId = $employeeId;
    }

    public function view(): View
    {
        $user = $this->employeeId;

        if(Auth()->user()->isApprover()){

            $query = ApprovalLayer::with(['employee','subordinates' => function ($query) use ($user){
                $query->with(['manager', 'goal', 'initiated', 'approvalLayer', 'updatedBy', 'approval' => function ($query) {
                    $query->with('approverName');
                }])->whereHas('approvalLayer', function ($query) use ($user) {
                    $query->where('employee_id', $user)->orWhere('approver_id', $user);
                })->whereYear('created_at', now()->year);
            }])
            ->leftJoin('approval_requests', 'approval_layers.employee_id', '=', 'approval_requests.employee_id')
            ->select('approval_layers.employee_id', 'approval_layers.approver_id', 'approval_layers.layer', 'approval_requests.created_at')
            ->whereYear('approval_requests.created_at', now()->year)
            ->whereHas('subordinates')->where('approver_id', $user);        
        }

        $data = $query->get();

        return view('exports.initiated', compact('data'));

    }

    public function styles($sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFFF00']]
            ],
        ];
    }
}
