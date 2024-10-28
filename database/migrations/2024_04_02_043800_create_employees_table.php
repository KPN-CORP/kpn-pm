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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 25)->unique();
            $table->foreign("employee_id")->references("employee_id")->on("users");
            $table->string('fullname', 100);
            $table->enum('gender', ['Male', 'Female']);
            $table->string('email', 100);
            $table->string('group_company', 50);
            $table->string('designation', 255);
            $table->string('job_level', 50);
            $table->string('company_name', 50);
            $table->string('work_area_code', 50);
            $table->string('office_area', 50);
            $table->string('manager_l1_id', 25);
            $table->string('manager_l2_id', 25);
            $table->enum('employee_type', ['Permanent', 'Contract', 'Probation', 'Service Bond']);
            $table->string('unit', 255);
            $table->date('date_of_joining');
            $table->string('contribution_level_code')->nullable();
            $table->json('access_menu')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
