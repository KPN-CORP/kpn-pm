<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calibration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['id_calibration_group',
    'appraisal_id',
    'employee_id',
    'approver_id',
    'period',
    'status',
    'rating',
    'created_by',
    'updated_by'];

    public function approver()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'approver_id', 'employee_id')->select(['id', 'employee_id', 'fullname']);
    }

    public function masterCalibration()
    {
        return $this->hasMany(MasterCalibration::class, 'id_calibration_group', 'id_calibration_group');
    }
}
