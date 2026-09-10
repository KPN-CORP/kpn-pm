<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLayer extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'approver_id', 'layer', 'updated_by'];

    /**
     * Ambil layer untuk banyak employee sekaligus, dikunci "employeeId-approverId".
     *
     * Menggantikan pola lama yang menjalankan satu query per baris di layar
     * list:
     *
     *     ApprovalLayer::where('employee_id', $x)->where('approver_id', $y)->value('layer')
     *
     * Pemakaian:
     *
     *     $layers = ApprovalLayer::layerMapFor($rows->pluck('employee_id'));
     *     $layer  = $layers[$row->employee_id.'-'.$row->current_approval_id] ?? null;
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
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function subordinates()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'employee_id');
    }

    public function manager()
    {
        return $this->belongsTo(ApprovalLayer::class, 'approver_id');
    }
    public function previousApprovers()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'approver_id');
    }
    public function view_employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
