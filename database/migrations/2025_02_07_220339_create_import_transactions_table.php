<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('import_transactions', function (Blueprint $table) {
            // Change the 'id' column to unsigned big integer
            $table->unsignedBigInteger('id');
            $table->string('imports_category');
            $table->text('path');
            $table->enum('status', ['done', 'failed']);
            $table->unsignedBigInteger('created_by');
            $table->text('error_messages')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_transactions');
    }
};
