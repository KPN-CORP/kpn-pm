<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLayerAppraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 
        'approver_id',
        'layer_type', 
        'layer', 
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'employee_id');
    }
    public function approvalRequest()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'employee_id');
    }
    public function contributors()
    {
        return $this->hasMany(AppraisalContributor::class, 'employee_id', 'employee_id');
    }
    public function previousApprovers()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'approver_id');
    }
    public function view_employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'employee_id', 'employee_id');
    }
}
