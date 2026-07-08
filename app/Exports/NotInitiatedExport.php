<?php

namespace App\Exports;

use App\Models\ApprovalLayer;
use App\Models\Employee;
use App\Services\AppService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class NotInitiatedExport implements FromView, WithStyles
{
    use Exportable;

    protected $employeeId;
    protected $period;
    protected $category;
    protected $data;

    public function __construct($employeeId, $period)
    {
        $this->category = 'Goals';
        $this->employeeId = $employeeId;
        $this->period = $period;
    }

    public function view(): View
    {
        $user = $this->employeeId;

        if(Auth()->user()->isApprover()){

            $this->data = ApprovalLayer::with('employee')
            ->where('approver_id', $user)
            ->whereHas('employee', fn($q) => $q->where('access_menu->doj', 1))
            ->whereHas('employee', fn($q) => $q->whereNull('deleted_at'))
            ->whereDoesntHave('subordinates', function ($query) use ($user) {
                $query->where('period', $this->period)
                    ->where('category', $this->category)
                    ->where('approver_id', $user);
            }) // Ensures subordinates with these criteria do NOT exist
            ->get();
    
            $this->data->map(function($item) {
                // Format created_at
                $doj = Carbon::parse($item->employee->date_of_joining);
    
                    $item->formatted_doj = $doj->format('d M Y');
                    
                return $item;
            });
        
        } else {
            $this->data = collect(); // Ensure it's always set
        }

        return view('exports.notInitiated', ['data' => $this->data]);

    }

    public function styles($sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Count total rows from $data and multiply by 10
        $totalRows = isset($this->data) ? count($this->data) * 10 : 10;


        // ====================
        // Column G - Performance Type
        // ====================
        $validationPerformance = $sheet->getCell('K2')->getDataValidation();

        $validationPerformance->setType(DataValidation::TYPE_LIST);
        $validationPerformance->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validationPerformance->setAllowBlank(false);
        $validationPerformance->setShowDropDown(true);

        $validationPerformance->setFormula1(
            '"Lower Better,Higher Better,Exact Value"'
        );

        for ($row = 2; $row <= $totalRows; $row++) {
            $sheet->getCell("K$row")
                ->setDataValidation(clone $validationPerformance);
        }


        // ====================
        // Column H - Review Period
        // ====================
        $validationReview = $sheet->getCell('L2')->getDataValidation();

        $validationReview->setType(DataValidation::TYPE_LIST);
        $validationReview->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validationReview->setAllowBlank(false);
        $validationReview->setShowDropDown(true);

        $validationReview->setFormula1(
            '"Monthly,Bi-Monthly,Quarterly,Semester,Annual"'
        );

        for ($row = 2; $row <= $totalRows; $row++) {
            $sheet->getCell("L$row")
                ->setDataValidation(clone $validationReview);
        }


        // ====================
        // Column I - Calculation Method
        // ====================
        $validationCalculation = $sheet->getCell('M2')->getDataValidation();

        $validationCalculation->setType(DataValidation::TYPE_LIST);
        $validationCalculation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validationCalculation->setAllowBlank(false);
        $validationCalculation->setShowDropDown(true);

        $validationCalculation->setFormula1(
            '"Average,Sum/Total,Last Value,Max,Min"'
        );

        for ($row = 2; $row <= $totalRows; $row++) {
            $sheet->getCell("M$row")
                ->setDataValidation(clone $validationCalculation);
        }

            // Apply percentage format to column (e.g., column C)
        $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

            return [
                1 => [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFFF00']]
                ],
            ];
        }
}
