<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use Carbon\Carbon;
use Spatie\Permission\PermissionRegistrar;

class DailyUpdateSchedulePA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-update-schedulepa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily update for schedule permission assignments for schedulepa';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = date('Y-m-d');
        $scheduledatas = Schedule::where('event_type', 'masterschedulepa')->get();

        foreach ($scheduledatas as $scheduledata) {

            $idpermissions = Permission::where('name', 'schedulepa')->first();
            $idschedulepa = $idpermissions->id;

            // Cek role yang memiliki permission_id 6 dan bukan role_id 1 atau 8
            $idroles = RoleHasPermission::where('permission_id', '6')
                                        ->whereNotIn('role_id', ['1', '8'])
                                        ->get();

            if ($scheduledata->start_date == $today) {
                foreach ($idroles as $idrole) {
                    $existingPermission = RoleHasPermission::where('role_id', $idrole->role_id)
                                                           ->where('permission_id', $idschedulepa)
                                                           ->first();
                    if (!$existingPermission) {
                        RoleHasPermission::create([
                            'role_id' => $idrole->role_id,
                            'permission_id' => $idschedulepa,
                        ]);
                    }
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            // Tambahkan 1 hari ke end_date
            $scheduledata->end_date = Carbon::parse($scheduledata->end_date)->addDay()->format('Y-m-d');

            if ($scheduledata->end_date == $today) {
                foreach ($idroles as $idrole) {
                    RoleHasPermission::where('role_id', $idrole->role_id)
                                     ->where('permission_id', $idschedulepa)
                                     ->delete();
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }
    }
}