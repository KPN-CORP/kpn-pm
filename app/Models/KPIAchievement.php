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
        'goal_id',
        'kpi_index',
        'month',
        'value'
    ];

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'current_approver_employee_id', 'employee_id');
    }
}
