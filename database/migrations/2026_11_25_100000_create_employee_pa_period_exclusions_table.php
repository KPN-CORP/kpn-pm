<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Karyawan yang dikeluarkan dari appraisal untuk SATU periode saja
 * (Settings > Employee > Terminate > "hanya periode ini"). Periode lain tetap
 * menampilkan karyawan tersebut. Dibaca oleh EmployeeAppraisal::periodMember().
 *
 * Defensif (cek hasTable) karena tabel migrations di server tidak sinkron —
 * jalankan dengan --path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_pa_period_exclusions')) {
            return;
        }

        Schema::create('employee_pa_period_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 50);
            $table->integer('period');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // users.id
            $table->timestamps();

            $table->unique(['employee_id', 'period'], 'eppe_employee_period_unique');
            $table->index('period', 'eppe_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_pa_period_exclusions');
    }
};
