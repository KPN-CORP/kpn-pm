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
        Schema::create('master_calibrations', function (Blueprint $table) {
            $table->id();
            $table->char('id_rating_group', 36);
            $table->smallInteger('period'); // Changed to smallInteger
            $table->string('grade', 1);
            $table->json('percentage')->nullable();
            $table->timestamps();
        
            // Optional Indexes
            $table->index('id_rating_group');
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_calibrations');
    }
};
