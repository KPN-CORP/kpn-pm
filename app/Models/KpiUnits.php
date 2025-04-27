<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiUnits extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function masterCalibration()
    {
        return $this->belongsTo(MasterCalibration::class, 'kpi_unit', 'grade');
    }
}
