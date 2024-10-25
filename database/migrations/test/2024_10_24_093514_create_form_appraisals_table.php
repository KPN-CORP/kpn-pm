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
        Schema::create('form_appraisals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('title')->nullable();
            $table->json('data')->nullable();  // Stores the array of criteria, items, and scores
            $table->string('icon')->nullable();
            $table->string('blade')->nullable();
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
        Schema::dropIfExists('form_appraisals');
    }
};
