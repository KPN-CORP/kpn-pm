<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * PA-only data per employee, layered over the live `employees` row.
 *
 * `employees_pa` is a view: `employees` LEFT JOIN this table. Employee
 * information always comes from employees; this table only holds the PA
 * access flags (`access_menu`) and the PA termination (`deleted_at`).
 */
class EmployeePaProfile extends Model
{
    protected $table = 'employee_pa_profiles';

    protected $guarded = ['id'];

    /** The only employees_pa columns that can be written. */
    public const WRITABLE = ['access_menu', 'deleted_at', 'deleted_by'];

    /** Bookkeeping columns written by Eloquent that the view derives itself. */
    private const IGNORED = ['updated_at', 'created_at', 'updated_by'];

    /**
     * Write changed employees_pa attributes for one employee into its profile.
     */
    public static function writeFor(string $employeeId, array $attributes): void
    {
        $attributes = array_diff_key($attributes, array_flip(self::IGNORED));

        $unknown = array_diff(array_keys($attributes), self::WRITABLE);
        if ($unknown) {
            throw new LogicException(
                'employees_pa is a view; these columns come from employees and cannot be written: '.implode(', ', $unknown)
            );
        }

        if (! $attributes) {
            return;
        }

        $now = now();

        DB::table('employee_pa_profiles')->upsert(
            [$attributes + ['employee_id' => $employeeId, 'created_at' => $now, 'updated_at' => $now]],
            ['employee_id'],
            array_merge(array_keys($attributes), ['updated_at'])
        );
    }
}
