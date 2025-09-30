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
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('module_transaction_id');
            $table->string('flow_name');
            $table->text('description')->nullable();
            $table->json('assignments');
            $table->json('initiator');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
