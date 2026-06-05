<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KPIAchievement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'kpi_achievements';

    protected $fillable = [
        "goal_id",
        "kpi_id",
        "month",
        "value",
        "file",
        "current_approver_employee_id",
        "approval_status",
        "approval_date",
        "approval_info",
        "created_at",
        "updated_at",
        "deleted_at"
    ];

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'current_approver_employee_id', 'employee_id');
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goal_id', 'id');
    }
}
