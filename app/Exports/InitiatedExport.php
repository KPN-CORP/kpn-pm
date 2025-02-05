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
    protected $filterYear;

    public function __construct($employeeId, $filterYear)
    {
        $this->employeeId = $employeeId;
        $this->filterYear = $filterYear;
    }

    public function view(): View
    {
        $user = $this->employeeId;
        $year = $this->filterYear;

        if(Auth()->user()->isApprover()){

            $query = ApprovalLayer::with(['employee', 'subordinates' => function ($query) use ($user, $year) {
                $query->with(['goal', 'updatedBy', 'approval' => function ($query) {
                    $query->with('approverName');
                }])->whereHas('goal', function ($query) {
                    $query->whereNull('deleted_at');
                })->whereHas('approvalLayer', function ($query) use ($user) {
                    $query->where('employee_id', $user)->orWhere('approver_id', $user);
                })->when($year, function ($query) use ($year) {
                    $query->where('period', $year); // Apply period condition only if $year is not empty
                });
            }])
            ->whereHas('subordinates', function ($query) use ($user, $year) {
                $query->with(['goal', 'updatedBy', 'approval' => function ($query) {
                    $query->with('approverName');
                }])->whereHas('goal', function ($query) {
                    $query->whereNull('deleted_at');
                })->whereHas('approvalLayer', function ($query) use ($user) {
                    $query->where('employee_id', $user)->orWhere('approver_id', $user);
                })->when($year, function ($query) use ($year) {
                    $query->where('period', $year); // Apply period condition only if $filterYear is not empty
                });
            })
            ->where('approver_id', $user);        
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
