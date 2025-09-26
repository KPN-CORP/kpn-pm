<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlowStep extends Model
{
    use HasFactory;

    protected $fillable = ['approval_flow_id','step_number','approver_role','approver_user_id','step_name','required_action','allotted_time'];
    protected $casts = ['approver_role'=>'array','approver_user_id'=>'array','step_number'=>'integer','allotted_time'=>'integer'];
    public function flow(){ return $this->belongsTo(ApprovalFlow::class,'approval_flow_id'); }
}
