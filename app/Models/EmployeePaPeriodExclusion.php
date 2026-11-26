<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Karyawan yang dikeluarkan dari appraisal untuk satu periode saja.
 * Lihat EmployeeAppraisal::scopePeriodMember().
 */
class EmployeePaPeriodExclusion extends Model
{
    protected $table = 'employee_pa_period_exclusions';

    protected $fillable = ['employee_id', 'period', 'reason', 'created_by'];

    protected $casts = ['period' => 'integer'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
