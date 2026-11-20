<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAppraisal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        // Kolom-kolom lainnya,
        'access_menu','id','employee_id', 'fullname', 'gender', 'email', 'group_company',
        'designation', 'designation_name','designation_code','job_level', 'company_name', 'contribution_level_code',
        'work_area_code', 'office_area', 'manager_l1_id', 'manager_l2_id',
        'employee_type', 'unit', 'date_of_joining', 'users_id', 'updated_by', 'deleted_by'
    ];

    // View: employees LEFT JOIN employee_pa_profiles (see migration
    // 2026_11_20_100000_split_employees_pa_into_profiles). Reads work like a
    // table; writes are routed to EmployeePaProfile below.
    protected $table = 'employees_pa';

    /**
     * Save changes into employee_pa_profiles instead of the (read-only) view.
     * Only access_menu / deleted_at are writable; employee information comes
     * from employees and is never changed from PA.
     */
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            EmployeePaProfile::writeFor((string) $this->employee_id, $dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Rows come from employees (HR sync); PA cannot create employees.
     */
    protected function performInsert(\Illuminate\Database\Eloquent\Builder $query)
    {
        throw new \LogicException('employees_pa is a view over employees; create the employee in employees instead.');
    }

    /**
     * PA terminate: mark the profile deleted; the employees row is untouched.
     */
    protected function runSoftDelete()
    {
        $time = $this->freshTimestamp();

        EmployeePaProfile::writeFor((string) $this->employee_id, [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            'deleted_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $this->{$this->getDeletedAtColumn()} = $time;

        $this->syncOriginalAttributes([$this->getDeletedAtColumn()]);

        $this->fireModelEvent('trashed', false);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
    public function goal()
    {
        return $this->belongsTo(Goal::class, 'employee_id', 'employee_id');
    }

    public function approvalRequest()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'employee_id');
    }

    public function approvalLayer()
    {
        return $this->hasMany(ApprovalLayer::class, 'employee_id', 'id');
    }
    public function approvalManager()
    {
        return $this->hasMany(ApprovalRequest::class, 'employee_id', 'current_approval_id');
    }
    public function creatorApproverLayer()
    {
        return $this->hasMany(ApprovalLayer::class, 'creator_id', 'id');
    }
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'bisnis_unit', 'group_company')
                ->whereRaw("FIND_IN_SET('bisnis_unit', group_company)");
    }
    public static function getUniqueGroupCompanies()
    {
        // Ambil data group_company yang unik dari tabel employee
        return self::select('group_company')
            ->distinct()
            ->pluck('group_company');
    }

    public function appraisalLayer()
    {
        return $this->hasMany(ApprovalLayerAppraisal::class, 'employee_id', 'employee_id');
    }
    public function appraisalpa()
    {
        return $this->belongsTo(Appraisal::class, 'employee_id', 'employee_id');
    }

    public function appraisalContributor()
    {
        return $this->hasMany(AppraisalContributor::class, 'employee_id', 'employee_id');
    }

    public function calibration()
    {
        return $this->hasMany(Calibration::class, 'employee_id', 'employee_id');
    }

    public function contributors()
    {
        return $this->hasMany(AppraisalContributor::class, 'employee_id', 'employee_id');
    }

    public function calibrations()
    {
        return $this->hasMany(Calibration::class, 'employee_id', 'employee_id');
    }

    public function appraisal()
    {
        return $this->hasMany(Appraisal::class, 'employee_id', 'employee_id');
    }

    public function kpiUnit()
    {
        return $this->hasMany(KpiUnits::class, 'employee_id', 'employee_id');
    }
}
