<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KPIAchievementSnapshot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'kpi_achievement_snapshots';

    protected $fillable = [
        "kpi_achievement_id",
        "goal_id",
        "kpi_id",
        "month",
        "value",
        "file",
        "employee_id",
        "current_approver_employee_id",
        "approval_status",
        "approval_date",
        "approval_info",
        "created_by",
        "created_at",
        "updated_at",
        "deleted_at"
    ];
}
