<?php

namespace App\Exports;

use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;

class InitiatedExport implements FromView, WithStyles
{
    use Exportable;

    protected $employeeId;
    protected $period;

    public function __construct($employeeId, $period)
    {
        $this->employeeId = $employeeId;
        $this->period = $period;
    }

    public function view(): View
    {
        $user = $this->employeeId;

        if(Auth()->user()->isApprover()){

            $query = ApprovalRequest::query();

            $query->where('category', 'Goals')->where('period', $this->period);

            $query->whereHas('approvalLayer', function ($query) {
                $query->where('approver_id', Auth::user()->employee_id);
            });

            $data = $query->with(['employee', 'manager', 'goal', 'initiated', 'approvalLayer'])->get();
            
        }
        
        // $data = $query->get();
        // dd($data);

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
