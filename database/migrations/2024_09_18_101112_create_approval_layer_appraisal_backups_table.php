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
        Schema::create('approval_layer_appraisal_backups', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('approver_id');
            $table->enum('layer_type', ['manager','subordinate','peers','calibrator']);
            $table->enum('layer', ['1','2','3','4','5','6','7','8','9','10']);
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_layer_appraisal_backups');
    }
};
