<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormGroupAppraisal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'form_number',
        'form_names',
        'restrict'
    ];

    protected $casts = [
        'form_names' => 'array',
        'restrict' => 'array'
    ];

    public function formAppraisals()
    {
        return $this->belongsToMany(FormAppraisal::class, 'form_group_appraisal_items')
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }
    public function rating()
    {
        return $this->hasMany(MasterRating::class, 'id_rating_group', 'id_rating_group');
    }

}
