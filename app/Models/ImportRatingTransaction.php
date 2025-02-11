<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportRatingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'file_name',
        'path',
        'status',
        'error_files',
        'error_path',
        'desc',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'created_by', 'id');
    }
} 