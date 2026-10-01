<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLayerAppraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'approver_id',
        'layer_type',
        'layer',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    /**
     * Ambil layer untuk banyak employee sekaligus, dikunci "employeeId-approverId".
     * Padanan ApprovalLayer::layerMapFor() untuk jalur appraisal — menggantikan
     * satu query per baris di layar list.
     *
     * @param  iterable  $employeeIds
     * @return array<string, mixed>
     */
    public static function layerMapFor($employeeIds): array
    {
        $employeeIds = collect($employeeIds)->filter()->unique()->values();

        if ($employeeIds->isEmpty()) {
            return [];
        }

        // PENTING: data punya pasangan (employee_id, approver_id) ganda —
        // orang yang sama terdaftar sebagai layer 1 DAN layer 2 untuk
        // karyawan yang sama (~50 pasangan di produksi). Query lama
        // ->value('layer') tanpa ORDER BY mengembalikan baris ber-id
        // terkecil, jadi di sini urutkan id menaik dan pertahankan yang
        // PERTAMA per pasangan. Tanpa ini badge "Manager L1" berubah jadi
        // "Manager L2" untuk karyawan-karyawan tersebut.
        $map = [];

        static::query()
            ->select(['id', 'employee_id', 'approver_id', 'layer'])
            ->whereIn('employee_id', $employeeIds)
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$map) {
                $map[$row->employee_id.'-'.$row->approver_id] ??= $row->layer;
            });

        return $map;
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_id', 'employee_id');
    }
    public function approver()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'approver_id', 'employee_id')->withTrashed();
    }
    public function approvalRequest()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'employee_id')->where('category', 'appraisal');
    }
    public function approvalRequestApprover()
    {
        return $this->hasMany(ApprovalRequest::class, 'approver_id', 'current_approval_id')->where('category', 'appraisal');
    }
    public function contributors()
    {
        return $this->hasMany(AppraisalContributor::class, 'employee_id', 'employee_id');
    }
    public function previousApprovers()
    {
        return $this->hasMany(EmployeeAppraisal::class, 'employee_id', 'approver_id');
    }
    public function view_employee()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_id', 'employee_id');
    }
    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'employee_id', 'employee_id');
    }
    public function createBy()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'created_by', 'id')->select('id', 'employee_id', 'fullname');
    }
    public function updateBy()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'updated_by', 'id')->select('id', 'employee_id', 'fullname');
    }
    public function goal()
    {
        return $this->hasMany(Goal::class, 'employee_id', 'employee_id');
    }
}
