<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        // Kolom-kolom lainnya,
        'parent_company_id','designation_name','job_code','department_name','department_code','department_level1','department_level2','department_level3','department_level4','department_level5','department_level6','department_level7','department_level8','department_level9','type_of_staffing_model','number_of_positions','number_of_existing_incumbents','department_hierarchy','status'
    ];
}
