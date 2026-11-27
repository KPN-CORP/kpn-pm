<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

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

    protected $table = 'appraisal_contributors';

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id', 'id');
    }
    public function goal()
    {
        return $this->belongsTo(Goal::class, 'employee_id', 'employee_id');
    }
    public function employee()
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_id', 'employee_id')
            // Data ini sudah milik satu periode: karyawan yang resign sesudahnya
            // tetap harus termuat.
            ->withTrashed();
    }

    /**
     * Tautkan ulang baris contributor (dan snapshot-nya) yang appraisal_id-nya tidak ada sama sekali
     * di tabel appraisals (termasuk soft-deleted) ke appraisal aktif karyawan.
     *
     * Kasus: review 360 tersimpan saat karyawan belum punya appraisal (appraisal lama dihapus, belum
     * di-initiate ulang) dengan UUID acak, sehingga tidak tampil di report.
     * Baris yang menunjuk appraisal soft-deleted tidak disentuh: itu milik appraisal lama.
     * Dilewati kalau kontributor yang sama sudah punya baris untuk appraisal aktif.
     */
    public static function relinkOrphans(string $employeeId, $period, string $appraisalId): void
    {
        $candidates = static::where('employee_id', $employeeId)
            ->where('period', $period)
            ->where('appraisal_id', '!=', $appraisalId)
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $existingIds = Appraisal::withTrashed()
            ->whereIn('id', $candidates->pluck('appraisal_id')->unique()->values())
            ->pluck('id')
            ->flip();

        $linkedContributors = static::where('appraisal_id', $appraisalId)
            ->pluck('contributor_id')
            ->flip();

        foreach ($candidates as $row) {
            if ($existingIds->has($row->appraisal_id) || $linkedContributors->has($row->contributor_id)) {
                continue;
            }

            ApprovalSnapshots::where('form_id', $row->appraisal_id)
                ->where('employee_id', $employeeId)
                ->update(['form_id' => $appraisalId]);

            Log::info('Relinked orphan appraisal contributor', [
                'contributor_row' => $row->id,
                'employee_id' => $employeeId,
                'from_appraisal_id' => $row->appraisal_id,
                'to_appraisal_id' => $appraisalId,
            ]);

            $row->update(['appraisal_id' => $appraisalId]);
            $linkedContributors->put($row->contributor_id, true);
        }
    }
}
