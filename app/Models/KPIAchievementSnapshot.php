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
        'goal_id',
        'kpi_index',
        'month',
        'value',
        'file',
        'employee_id',
        'created_by'
    ];
}
