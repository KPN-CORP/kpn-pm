<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRating extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        // Kolom-kolom lainnya,
        'rating_group_name','parameter','value', 'desc', 'add_range', 'min_range', 'max_range'
    ];
    protected $dates = ['deleted_at'];

    public function createdBy()
    {
        return $this->belongsTo(user::class, 'created_by','id');
    }
}
