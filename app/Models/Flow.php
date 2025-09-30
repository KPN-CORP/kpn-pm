<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_transaction',
        'flow_name',
        'description',
        'assignments',
        'initiator',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assignments' => 'array',
        'initiator'   => 'array',
    ];
}
