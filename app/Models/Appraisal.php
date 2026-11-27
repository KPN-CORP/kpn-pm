<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Appraisal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['form_data', 'form_status', 'updated_by', 'file'];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'id', 'form_id');
    }
    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goals_id', 'id');
    }
    public function employee()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_id', 'employee_id')
            // Data ini sudah milik satu periode: karyawan yang resign sesudahnya
            // tetap harus termuat.
            ->withTrashed();
    }
    public function formGroupAppraisal()
    {
        return $this->belongsTo(FormGroupAppraisal::class, 'form_group_id', 'id');
    }
    public function approvalSnapshots()
    {
        return $this->belongsTo(ApprovalSnapshots::class, 'id', 'form_id');
    }

    /**
     * Draft self appraisal karyawan pada periode tsb yang BELUM disentuh manager ini.
     *
     * Dipakai untuk menyembunyikan karyawan dari team list / notifikasi Task Box selama
     * karyawan masih mengisi self appraisal. Draft yang dibuat manager sendiri
     * (initiate -> save as draft) punya baris appraisal_contributors milik manager,
     * jadi tidak ikut tersembunyi dan bisa dilanjutkan.
     *
     * Wajib dibatasi periode: relasi ApprovalLayerAppraisal::appraisal hanya join
     * employee_id, tanpa periode draft lama ikut menyembunyikan karyawan.
     */
    public function scopeEmployeeDraftHiddenFrom($query, string $managerId, $period)
    {
        return $query->where('form_status', 'Draft')
            ->where('period', $period)
            ->whereNotExists(function ($sub) use ($managerId) {
                $sub->selectRaw('1')
                    ->from('appraisal_contributors')
                    ->whereColumn('appraisal_contributors.appraisal_id', 'appraisals.id')
                    ->where('appraisal_contributors.contributor_id', $managerId);
            });
    }
}
