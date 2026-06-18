<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AchievementExport implements FromView, WithStyles
{
    use Exportable;

    public function __construct(
        protected $data
    ) {}

    public function view(): View
    {
        $data = collect($this->data)->map(function ($row) {

            $row->review_period = match (strtolower($row->review_period ?? '')) {
                'monthly', '1' => 'Monthly',
                'bi-monthly', '2' => 'Bi-Monthly',
                'quarterly', '3' => 'Quarterly',
                'semester', '6' => 'Semester',
                'annual', '12' => 'Annual',
                default => $row->review_period
            };

            $row->calculation_method = match (strtolower($row->calculation_method ?? '')) {
                'average' => 'Average',
                'sum' => 'Sum/Total',
                'last' => 'Last Value',
                'max' => 'Max',
                'min' => 'Min',
                default => $row->calculation_method
            };

            return $row;
        });

        return view(
            'exports.achievement',
            compact('data')
        );
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells('L1:W1');
        $sheet->setCellValue('L1', 'Achievement');

        $sheet->getStyle('A1:W2')->applyFromArray([
            'font'=>[
                'bold'=>true
            ],
            'alignment'=>[
                'horizontal'=>'center',
                'vertical'=>'center'
            ],
            'borders'=>[
                'allBorders'=>[
                    'borderStyle'=>Border::BORDER_THIN
                ]
            ]
        ]);

        // yellow fill
        $sheet->getStyle('L1:W2')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('FFFFCC');

        $sheet->getStyle("L3:W{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        $sheet->getStyle("F3:F{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        $monthColumns = [
            'L' => 1,
            'M' => 2,
            'N' => 3,
            'O' => 4,
            'P' => 5,
            'Q' => 6,
            'R' => 7,
            'S' => 8,
            'T' => 9,
            'U' => 10,
            'V' => 11,
            'W' => 12,
        ];

        for($row=3;$row<=$highestRow;$row++){

            $period = strtolower(
                trim(
                    $sheet->getCell("J{$row}")
                        ->getValue()
                )
            );

            $reviewPeriod = match($period){
                'monthly'=>1,
                'bi-monthly'=>2,
                'quarterly'=>3,
                'semester'=>6,
                'annual'=>12,
                default=>1
            };

            foreach($monthColumns as $excelCol => $month){

                if($month % $reviewPeriod !== 0){

                    $sheet->getStyle(
                        "{$excelCol}{$row}"
                    )->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('707070');
                }
            }
        }

        $sheet->getStyle("A3:W{$highestRow}")
            ->applyFromArray([
                'borders'=>[
                    'allBorders'=>[
                        'borderStyle'=>Border::BORDER_THIN
                    ]
                ]
            ]);

        // center header row
        $sheet->getStyle("A2:W2")
            ->getAlignment()
            ->setHorizontal('center');

        $sheet->getStyle("A2:W2")
            ->getAlignment()
            ->setVertical('center');

        $sheet->freezePane('A3');

        foreach(range('A','W') as $col){
            $sheet->getColumnDimension($col)
                ->setAutoSize(true);
        }

        return [];
    }
}