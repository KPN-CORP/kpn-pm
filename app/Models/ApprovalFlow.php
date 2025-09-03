<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalFlow extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['flow_name','description','is_active','settings_json','created_by','updated_by','deleted_at'];
    protected $casts = ['settings_json'=>'array','is_active'=>'boolean'];
    public function steps(){ return $this->hasMany(ApprovalFlowStep::class,'approval_flow_id')->orderBy('step_number'); }
}