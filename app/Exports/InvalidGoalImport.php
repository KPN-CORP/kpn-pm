<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvalidGoalImport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $invalidEmployees;

    public function __construct($invalidEmployees)
    {
        $this->invalidEmployees = $invalidEmployees;
    }

    // Data to be exported
    public function collection()
    {
        return collect($this->invalidEmployees);
    }

    // Headings for the Excel sheet
    public function headings(): array
    {
        return [
            'employee_id',
            'errors'
        ];
    }
}
