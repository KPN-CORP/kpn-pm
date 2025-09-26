<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->increments('id'); // Menggunakan increments untuk INT PRIMARY KEY
            $table->string('flow_name', 100)->unique()->comment('Nama unik untuk alur persetujuan (contoh: "Alur Persetujuan Cuti", "Alur Persetujuan PO")');
            $table->text('description')->nullable()->comment('Deskripsi detail tentang alur ini');
            $table->boolean('is_active')->default(true)->comment('Menunjukkan apakah alur ini aktif dan dapat digunakan');
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
