<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalContributor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $fillable = [
        'appraisal_id',
        'employee_id',
        'contributor_id',
        'contributor_type',
        'form_data',
        'rating',
        'status',
        'period',
        'created_by',
        'updated_by',
    ];

    protected $table = 'appraisals_contributors';

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id', 'id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}