<?php

namespace App\Exports;

use App\Models\ApprovalLayerAppraisal;
use App\Models\User;
use App\Models\Employee;
use App\Models\PaReminder;
use Maatwebsite\Excel\Concerns\FromArray;

class ReminderExport implements FromArray
{
    protected $employee;
    protected $reminder;

    public function __construct(Employee $employee, PaReminder $reminder)
    {
        $this->employee = $employee;
        $this->reminder = $reminder;
    }

    public function array(): array
    {
        // Header
        $data = [
            ['Manager', 'Layer', 'Bawahan', 'Status', 'Input Date'],
        ];

        // Ambil semua bawahan dari tabel approval_layer_appraisals
        $layers = ApprovalLayerAppraisal::with('employee')
            ->where('approver_id', $this->employee->employee_id)
            ->whereIn('layer_type', ['manager'])
            ->get();

        foreach ($layers as $layer) { 
            $bawahan = $layer->employee ? "{$layer->employee->employee_id} - {$layer->employee->fullname}" : '-';
            $appraisal = $layer->appraisal ? $layer->appraisal->form_status : 'Not Yet';
            $inputdate = $layer->appraisal ? $layer->appraisal->created_at : '-';
            $data[] = [
                $this->employee->fullname,
                ucfirst($layer->layer_type),
                $bawahan,
                $appraisal,
                $inputdate,
            ];
        }

        // Jika tidak ada bawahan, tampilkan 1 baris default
        if (count($layers) === 0) {
            $data[] = [
                $this->employee->employee_id,
                '-',
                '-',
                'No Subordinates Found',
                '-',
            ];
        }

        return $data;
    }
}