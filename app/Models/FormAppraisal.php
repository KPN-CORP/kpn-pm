<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormAppraisal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'title',
        'data',
        'icon',
        'blade'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    // Relasi dengan form_group_appraisals
    public function formGroups()
    {
        return $this->belongsToMany(FormGroupAppraisal::class, 'form_group_appraisal_items')
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }

}
