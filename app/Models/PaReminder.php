<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaReminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reminder_name',
        'bisnis_unit',
        'company_filter',
        'location_filter',
        'grade',
        'start_date',
        'end_date',
        'includeList',
        'repeat_days',
        'messages',
        'created_by'
    ];
}
