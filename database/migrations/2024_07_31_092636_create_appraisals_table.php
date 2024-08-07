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
        Schema::create('appraisals', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->string('employee_id');
            $table->string('category');
            $table->json('form_data');
            $table->enum('form_status', ['Draft', 'Submitted', 'Approved', 'Rejected']);
            $table->text('messages')->nullable();
            $table->unsignedInteger('period');
            $table->unsignedInteger('rating');
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
        Schema::dropIfExists('appraisals');
    }
};
