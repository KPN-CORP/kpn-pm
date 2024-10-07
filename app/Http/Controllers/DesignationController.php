<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Designation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DesignationController extends Controller
{
    public function UpdateDesignation()
    {
        Log::info('UpdateDesignation method started.'); // Logging start

        // URL API
        $url = 'https://kpncorporation.darwinbox.com/orgmasterapi/designationlist';

        // Data untuk request
        $data = [
            "api_key" => "90cf9e8b2805425bb5a003220efdb7f275b8ef2c13ee2924546539c507dc3a39e2519b86187c038f84a29bbeb15f7e87d145b546e79b47d8271691b899d167e7"
        ];

        // Header
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ZGFyd2luYm94c3R1ZGlvOkRCc3R1ZGlvMTIzNDUh'
        ];

        try {
            Log::info('Sending request to API', ['url' => $url, 'data' => $data]); // Logging request details

            // Request ke API menggunakan Laravel Http Client
            $response = Http::withHeaders($headers)->post($url, $data);

            // Check response status
            if ($response->failed()) {
                Log::error('API request failed', ['status' => $response->status(), 'response' => $response->body()]);
                return response()->json(['message' => 'Failed to update designation data'], 500);
            }

            // Parse response
            $designations = $response->json('data');

            $number_data = 0;

            Log::info('API response received', ['designation_count' => count($designations)]);

            // Simpan data ke database
            foreach ($designations as $designation) {

                Designation::updateOrCreate(
                    ['job_code' => $designation['job_code']],
                    [
                        'parent_company_id' => $designation['parent_company_id'],
                        'designation_name' => $designation['designation_name'],
                        'job_code' => $designation['job_code'],
                        'department_name' => $designation['department_name'],
                        'department_code' => $designation['department_code'],
                        'department_level1' => $designation['department_level1'],
                        'department_level2' => $designation['department_level2'],
                        'department_level3' => $designation['department_level3'],
                        'department_level4' => $designation['department_level4'],
                        'department_level5' => $designation['department_level5'],
                        'department_level6' => $designation['department_level6'],
                        'department_level7' => $designation['department_level7'],
                        'department_level8' => $designation['department_level8'],
                        'department_level9' => $designation['department_level9'],
                        'type_of_staffing_model' => $designation['type_of_staffing_model'],
                        'number_of_positions' => $designation['number_of_positions'],
                        'number_of_existing_incumbents' => $designation['number_of_existing_incumbents'],
                        'department_hierarchy' => $designation['department_hierarchy'],
                        'status' => $designation['status']
                    ]
                );
                $number_data++;
            }

            Log::info('Update Designation data successfully saved', ['saved_count' => $number_data]);

            return response()->json(['message' => $number_data.' Update Designation data successfully saved']);
        } catch (\Exception $e) {
            Log::error('Exception occurred in UpdateDesignation method', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }
}
