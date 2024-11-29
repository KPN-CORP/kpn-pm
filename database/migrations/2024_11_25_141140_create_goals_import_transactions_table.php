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
        Schema::create('goals_import_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('success')->default(0); // Jumlah data berhasil diimport
            $table->integer('error')->default(0); // Jumlah data gagal diimport
            $table->string('file_uploads'); // Direktori file yang di-upload
            $table->unsignedBigInteger('submit_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals_import_transactions');
    }
};
