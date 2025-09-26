<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'approver_user_id',
        'action_taken',
        'comments',
        'action_timestamp',
    ];

    // Jika Anda tidak ingin Laravel mengelola created_at dan updated_at secara otomatis
    public $timestamps = false;

    /**
     * Mendefinisikan hubungan banyak-ke-satu dengan ApprovalRequest.
     */
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }
}
