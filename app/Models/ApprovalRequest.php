<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'current_approval_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'employee_id');
    }
    public function goal()
    {
        return $this->belongsTo(Goal::class, 'form_id', 'id');
    }
    public function approvalLayer()
    {
        return $this->hasMany(ApprovalLayer::class, 'employee_id', 'employee_id');
    }
    public function approvalLayerAppraisal()
    {
        return $this->hasMany(ApprovalLayerAppraisal::class, 'employee_id', 'employee_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'current_approval_id', 'employee_id');
    }
    public function approval()
    {
        return $this->hasMany(Approval::class, 'request_id');
    }
    public function initiated()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->select(['id', 'employee_id', 'name']);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->select(['id','employee_id', 'name']);
    }
    public function adjustedBy()
    {
        return $this->belongsTo(ModelHasRole::class, 'updated_by', 'model_id')->select(['model_id']);
    }
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'form_id', 'id');
    }
    public function contributor()
    {
        return $this->hasMany(AppraisalContributor::class, 'employee_id', 'employee_id');
    }

    public function calibrator()
    {
        return $this->belongsTo(ApprovalLayerAppraisal::class, 'current_approval_id', 'approver_id');
    }

}
