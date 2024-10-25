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
        Schema::create('form_group_appraisals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('form_number');
            $table->json('form_names');  // Stores array of form names ["KPI","Culture","Leadership"]
            $table->json('restrict')->nullable();  // Stores restrictions like job levels
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_group_appraisal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_group_appraisal_id')->constrained()->onDelete('cascade');
            $table->foreignId('form_appraisal_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
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
        Schema::dropIfExists('form_group_appraisal_items');
        Schema::dropIfExists('form_group_appraisals');
    }
};
