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
        Schema::create('employee_pa_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Relasi ke tabel employee
            $table->json('before')->nullable();   // Menyimpan data sebelum diupdate dalam JSON
            $table->json('after')->nullable();    // Menyimpan data sesudah diupdate dalam JSON
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_pa_histories');
    }
};
