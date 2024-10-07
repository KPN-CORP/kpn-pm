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
        Schema::create('master_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('id_rating_group', 36);
            $table->string('rating_group_name', 255);
            $table->string('parameter', 255);
            $table->text('desc');
            $table->double('min_range');
            $table->double('max_range');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_ratings');
    }
};
