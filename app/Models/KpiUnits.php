<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiUnits extends Model
{
    use HasFactory;

    public function masterCalibration()
    {
        return $this->belongsTo(MasterCalibration::class, 'kpi_unit', 'grade');
    }
}
