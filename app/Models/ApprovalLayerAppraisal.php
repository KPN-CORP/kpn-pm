<?php

namespace App\Models;

use App\Support\AppraisalLayerPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Layer appraisal (manager / peers / subordinate / calibrator) PER PERIODE.
 *
 * Global scope 'period' membatasi semua query ke AppraisalLayerPeriod::current()
 * (default: periode appraisal berjalan), dan baris baru otomatis diberi
 * periode itu. Untuk melihat/menulis periode lain pakai
 * AppraisalLayerPeriod::using($period, fn () => ...) atau
 * ApprovalLayerAppraisal::allPeriods().
 *
 * Catatan: query mentah DB::table('approval_layer_appraisals') TIDAK kena
 * scope ini, tambahkan filter period sendiri.
 */
class ApprovalLayerAppraisal extends Model
{
    use HasFactory;

    public const PERIOD_SCOPE = 'period';

    protected static function booted(): void
    {
        static::addGlobalScope(self::PERIOD_SCOPE, function (Builder $query) {
            $period = AppraisalLayerPeriod::current();
            if ($period) {
                $query->where($query->getModel()->qualifyColumn('period'), $period);
            }
        });

        static::creating(function (self $layer) {
            $layer->period ??= AppraisalLayerPeriod::current();
        });

        static::saved(fn () => AppraisalLayerPeriod::forgetAvailable());
    }

    /** Query tanpa batas periode (semua periode). */
    public static function allPeriods(): Builder
    {
        return static::withoutGlobalScope(self::PERIOD_SCOPE);
    }

    /**
     * Salin layer periode $from ke periode $to. Per karyawan, jenis layer
     * yang sudah ada di periode $to dilewati, supaya hasil import / edit
     * manual periode baru tidak tertimpa.
     *
     * @param  array<int, string>  $layerTypes
     * @param  array<int, string>|null  $employeeIds  hanya karyawan ini (null = semua)
     * @return array{employees: int, rows: int}
     */
    public static function duplicatePeriod(int $from, int $to, array $layerTypes, $userId, ?array $employeeIds = null): array
    {
        $layerTypes = array_values(array_intersect($layerTypes, ['manager', 'peers', 'subordinate', 'calibrator']));
        if ($from === $to || empty($layerTypes) || $employeeIds === []) {
            return ['employees' => 0, 'rows' => 0];
        }

        return DB::transaction(function () use ($from, $to, $layerTypes, $userId, $employeeIds) {
            $source = DB::table('approval_layer_appraisals as src')
                ->where('src.period', $from)
                ->whereIn('src.layer_type', $layerTypes)
                ->when($employeeIds !== null, fn ($q) => $q->whereIn('src.employee_id', $employeeIds))
                // jenis layer yang sudah diisi di periode tujuan tidak ditimpa
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('approval_layer_appraisals as dst')
                    ->where('dst.period', $to)
                    ->whereColumn('dst.employee_id', 'src.employee_id')
                    ->whereColumn('dst.layer_type', 'src.layer_type'))
                // hanya karyawan yang masih aktif di PA
                ->whereIn('src.employee_id', EmployeeAppraisal::query()->select('employee_id'));

            $employees = (clone $source)->distinct()->count('src.employee_id');

            $now = now();
            $rows = DB::table('approval_layer_appraisals')->insertUsing(
                ['employee_id', 'approver_id', 'layer_type', 'layer', 'period', 'created_by', 'created_at', 'updated_at'],
                $source->select([
                    'src.employee_id', 'src.approver_id', 'src.layer_type', 'src.layer',
                    DB::raw((int) $to),
                    DB::raw(DB::getPdo()->quote((string) $userId)),
                    DB::raw(DB::getPdo()->quote($now->toDateTimeString())),
                    DB::raw('NULL'),
                ])->orderBy('src.id')
            );

            AppraisalLayerPeriod::forgetAvailable();

            return ['employees' => $employees, 'rows' => $rows];
        });
    }

    protected $fillable = [
        'employee_id',
        'approver_id',
        'layer_type',
        'layer',
        'period',
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
