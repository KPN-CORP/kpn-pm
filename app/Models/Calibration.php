<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calibration extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'rating', 'updated_by'];

    public function approver()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'approver_id', 'employee_id')->select(['id', 'employee_id', 'fullname']);
    }
}
