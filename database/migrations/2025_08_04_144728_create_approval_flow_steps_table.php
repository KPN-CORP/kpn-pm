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
        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->bigIncrements('id'); // Menggunakan bigIncrements untuk BIGINT UNSIGNED PRIMARY KEY
            $table->unsignedInteger('approval_flow_id')->comment('ID alur persetujuan yang terkait');
            $table->integer('step_number')->comment('Nomor urutan langkah dalam alur (mulai dari 1)');
            $table->string('approver_role_or_user_id', 255)->comment('Bisa berupa ID peran (contoh: "Manajer", "HRD") atau ID pengguna spesifik');
            $table->string('step_name', 100)->nullable()->comment('Nama deskriptif untuk langkah ini (contoh: "Persetujuan Manajer Langsung")');
            $table->string('required_action', 50)->default('Approve')->comment('Tindakan yang diharapkan pada langkah ini (contoh: "Approve", "Review")');
            $table->timestamps(); // created_at dan updated_at

            // Definisi Foreign Key
            $table->foreign('approval_flow_id')
                  ->references('id')
                  ->on('approval_flows')
                  ->onDelete('cascade');

            // Indeks unik untuk memastikan langkah unik per alur
            $table->unique(['approval_flow_id', 'step_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
    }
};
