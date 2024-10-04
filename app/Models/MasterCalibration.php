<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCalibration extends Model
{
    use HasFactory;

    public function masterRating() {
        return $this->hasMany(MasterRating::class, 'id_rating_group', 'id_rating_group');
    }
}
