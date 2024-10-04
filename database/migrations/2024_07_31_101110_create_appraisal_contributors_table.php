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
        Schema::create('appraisal_contributors', function (Blueprint $table) {
            $table->id();
            $table->uuid('appraisal_id');
            $table->string('employee_id');
            $table->string('contributor_id');
            $table->string('contributor_type');
            $table->json('form_data');
            $table->unsignedInteger('rating');
            $table->enum('status', ['Draft','Submitted','Approved','Rejected'])->default('Submitted');
            $table->unsignedInteger('period');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_contributors');
    }
};
