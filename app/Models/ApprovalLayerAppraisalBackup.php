<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLayerAppraisalBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'approver_id', 'layer_type', 'layer', 'created_by'
    ];
}
