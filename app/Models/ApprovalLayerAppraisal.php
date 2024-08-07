<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLayerAppraisal extends Model
{
    use HasFactory;

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'employee_id');
    }
    public function subordinates()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'employee_id');
    }
    public function previousApprovers()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'approver_id');
    }
    public function view_employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
