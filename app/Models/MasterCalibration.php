<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCalibration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        // Kolom-kolom lainnya,
        'id_rating_group','id_calibration_group','kpi_unit','individual_kpi','name','grade','percentage','created_by'
    ];
    
    public function masterRating() {
        return $this->hasMany(MasterRating::class, 'id_rating_group', 'id_rating_group');
    }
    
    public function createdBy()
    {
        return $this->belongsTo(user::class, 'created_by','id');
    }
}
