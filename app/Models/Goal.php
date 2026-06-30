<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_id',
        'category',
        'form_data',
        'form_status',
        'period',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'id', 'form_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function employeeAppraisal()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_id', 'employee_id');
    }
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'id', 'goals_id');
    }
    public function achievement()
    {
        return $this->hasOne(KPIAchievement::class, 'goal_id', 'id');
    }
    public function achievementList()
    {
        return $this->hasMany(KPIAchievement::class, 'goal_id', 'id');
    }

}
