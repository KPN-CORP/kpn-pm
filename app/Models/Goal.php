<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'id', 'form_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'id', 'goals_id');
    }

}
