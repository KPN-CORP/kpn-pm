<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log setiap baris yang dipindah dari employee_id lama ke employee_id baru
 * untuk karyawan rehire (lihat App\Services\RehireMergeService). Dipakai untuk
 * audit dan untuk rollback manual: kembalikan `column_name` di `table_name`
 * baris `row_id` dari `new_employee_id` ke `old_employee_id`.
 *
 * Defensif (cek hasTable) karena tabel migrations di server tidak sinkron —
 * jalankan dengan --path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_id_merges')) {
            return;
        }

        Schema::create('employee_id_merges', function (Blueprint $table) {
            $table->id();
            $table->string('old_employee_id', 50);
            $table->string('new_employee_id', 50);
            $table->string('table_name', 64);
            $table->string('column_name', 64);
            $table->string('row_id', 64);
            // moved = sudah dipindah ke id baru, skipped = sengaja ditinggal di id lama
            $table->string('action', 20);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['old_employee_id', 'new_employee_id'], 'eim_old_new_idx');
            $table->index(['table_name', 'row_id'], 'eim_table_row_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_id_merges');
    }
};
