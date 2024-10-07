<?php

namespace App\Services;

use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\MasterRating;
use App\Models\MasterWeightage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use stdClass;

class AppService
{
    // Function to calculate the average score
    public function averageScore($formData) {
        $totalScore = 0;
        $totalCount = 0;
    
        foreach ($formData as $key => $section) {
            if (is_array($section)) {
                foreach ($section as $subSection) {
                    if (is_array($subSection) && isset($subSection['score'])) {
                        $totalScore += $subSection['score'];
                        $totalCount++;
                    }
                }
            }
        }
    
        if ($totalCount === 0) {
            return 0; // Prevent division by zero
        }
    
        return $totalScore / $totalCount;
    }
    

    public function evaluate($achievement, $target, $type) {
        switch (strtolower($type)) {
            case 'higher better':
                return ($achievement / $target) * 100;

            case 'lower better':
                return (2 - ($achievement / $target)) * 100;

            case 'exact value':
                return ($achievement == $target) ? 100 : 0;

            default:
                throw new Exception('Invalid type');
        }
    }

    public function conversion($evaluate) {
        if ($evaluate < 60) {
            return 1;
        } elseif ($evaluate >= 60 && $evaluate < 95) {
            return 2;
        } elseif ($evaluate >= 95 && $evaluate <= 100) {
            return 3;
        } elseif ($evaluate > 100 && $evaluate <= 120) {
            return 4;
        } else {
            return 5;
        }
    }

    public function combineFormData($appraisalData, $goalData, $typeWeightage360, $employeeData) {
        $totalKpiScore = 0; // Initialize the total score
        $cultureAverageScore = 0; // Initialize Culture average score
        $leadershipAverageScore = 0; // Initialize Culture average score

        $jobLevel = $employeeData->job_level;
        
        $appraisalDatas = $appraisalData;
    
        foreach ($appraisalDatas['formData'] as &$form) {
            if ($form['formName'] === "KPI") {
                foreach ($form as $key => &$entry) {
                    if (is_array($entry) && isset($goalData[$key])) {
                        $entry = array_merge($entry, $goalData[$key]);
    
                        // Adding "percentage" key
                        if (isset($entry['achievement'], $entry['target'], $entry['type'])) {
                            $entry['percentage'] = $this->evaluate($entry['achievement'], $entry['target'], $entry['type']);
                            $entry['conversion'] = $this->conversion($entry['percentage']);
                            $entry['final_score'] = $entry['conversion'] * $entry['weightage'] / 100;
    
                            // Add the final_score to the total score
                            $totalKpiScore += $entry['final_score'];
                        }
                    }
                }
            } elseif ($form['formName'] === "Culture") {
                // Calculate average score for Culture form
                $cultureAverageScore = $this->averageScore($form);
            } elseif ($form['formName'] === "Leadership") {
                // Calculate average score for Culture form
                $leadershipAverageScore = $this->averageScore($form);
            }
        }

        $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->first();
        
        $weightageContent = json_decode($weightageData->form_data, true);

        $kpiWeightage = 0;
        $cultureWeightage = 0;
        $leadershipWeightage = 0;
        // $typeWeightage360 = 'Manager';

        foreach ($weightageContent as $item) {
            if (in_array($jobLevel, $item['jobLevel'])) {
                foreach ($item['competencies'] as $competency) {

                    $employeeWeightage = 0;

                    if (isset($competency['weightage360'])) {
                        foreach ($competency['weightage360'] as $weightage360) {
                            if (isset($weightage360[$typeWeightage360])) {
                                $employeeWeightage += $weightage360[$typeWeightage360];
                            }
                        }
                    }

                    switch ($competency['competency']) {
                        case 'Key Performance Indicator':
                            $kpiWeightage = $competency['weightage'];
                            $kpiWeightage360 = $employeeWeightage;
                            break;
                        case 'Culture':
                            $cultureWeightage = $competency['weightage'];
                            $cultureWeightage360 = $employeeWeightage;
                            break;
                        case 'Leadership':
                            $leadershipWeightage = $competency['weightage'];
                            $leadershipWeightage360 = $employeeWeightage;
                            break;
                    }
                }
                break; // Exit after processing the relevant job level
            }
        }
    
        $appraisalDatas['kpiWeightage360'] = $kpiWeightage360; // get KPI 360 weightage
        $appraisalDatas['cultureWeightage360'] = $cultureWeightage360 / 100; // get Culture 360 weightage
        $appraisalDatas['leadershipWeightage360'] = $leadershipWeightage360 / 100; // get Leadership 360 weightage
        
        // Add the total scores to the appraisalData
        $appraisalDatas['totalKpiScore'] = $totalKpiScore * $kpiWeightage / 100; // get KPI Final Score
        $appraisalDatas['cultureAverageScore'] = ($cultureAverageScore * $cultureWeightage / 100) * $appraisalDatas['cultureWeightage360']; // get Culture Average Score
        $appraisalDatas['leadershipAverageScore'] = ($leadershipAverageScore * $leadershipWeightage / 100) * $appraisalDatas['leadershipWeightage360']; // get Leadership Average Score
        
        $appraisalDatas['contributorRating'] = $appraisalDatas['totalKpiScore'] + $appraisalDatas['cultureAverageScore'] + $appraisalDatas['leadershipAverageScore'];
    
        return $appraisalDatas;
    }

    // Function to merge scores
    function mergeScores($formData, $filteredFormData) {
        foreach ($formData['formData'] as $formData) {
            $formName = $formData['formName'];
            foreach ($filteredFormData as &$section) {
                if ($section['name'] === $formName) {
                    foreach ($formData as $key => $value) {
                        if (is_numeric($key)) {
                            if (isset($value['score'])) {
                                foreach ($value['score'] as $scoreIndex => $scoreValue) {
                                    if (isset($section['data'][$key]['score'][$scoreIndex])) {
                                        $section['data'][$key]['score'][$scoreIndex] += $scoreValue;
                                    } else {
                                        $section['data'][$key]['score'][$scoreIndex] = $scoreValue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $filteredFormData;
    }

    function formatDate($date)
    {
        // Parse the date using Carbon
        $carbonDate = Carbon::parse($date);

        // Check if the date is today
        if ($carbonDate->isToday()) {
            return 'Today ' . $carbonDate->format('ga');
        } else {
            return $carbonDate->format('d M Y');
        }
    }

    private function mergeFormData(array $formDataSets)
    {
        $mergedData = [];

        foreach ($formDataSets as $formData) {
            foreach ($formData['formData'] as $form) {

                $formName = $form['formName'];

                // Check if formName already exists in the merged data
                $existingFormIndex = collect($mergedData)->search(function ($item) use ($formName) {
                    return $item['formName'] === $formName;
                });

                if ($existingFormIndex !== false) {
                    // Merge scores for the existing form
                    foreach ($form as $key => $scores) {
                        if (is_numeric($key)) {
                            $mergedData[$existingFormIndex][$key] = array_merge($mergedData[$existingFormIndex][$key] ?? [], $scores);
                        }
                    }
                } else {
                    // Add the form to the merged data
                    $mergedData[] = $form;
                }
            }
        }

        return [
            'formGroupName' => 'Appraisal Form',
            'formData' => $mergedData
        ];

    }

    function suggestedRating($id, $category)
    {
        try {
            // Retrieve approval requests
            $datasQuery = ApprovalRequest::with([
                'employee', 'appraisal', 'updatedBy', 'adjustedBy', 'initiated', 'manager', 'contributor',
                'approval' => function ($query) {
                    $query->with('approverName');
                }
            ])
            ->whereHas('approvalLayerAppraisal', function ($query) use ($id) {
                $query->where('employee_id', $id)->orWhere('approver_id', $id);
            })
            ->where('employee_id', $id)->where('category', $category);

            if (!empty($filterYear)) {
                $datasQuery->whereYear('created_at', $filterYear);
            }

            $datas = $datasQuery->get();

            $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->goal->form_data, true) : [];
            $appraisalData = $datas->isNotEmpty() ? json_decode($datas->first()->appraisal->form_data, true) : [];
            
            $groupedContributors = $datas->first()->contributor->groupBy('contributor_type');
            
            $mergedResults = [];
            
            // Kumpulan data berdasarkan contributor_type
            $contributorManagerContent = [];
            $combinedPeersData = [];
            $combinedSubData = [];

            // Gabungkan formData untuk setiap contributor_type
            foreach ($groupedContributors as $type => $contributors) {
                // Siapkan array untuk menampung formData dari kontributor dalam grup
                $formDataSets = [];

                foreach ($contributors as $contributor) {
                    // Decode form_data JSON dari setiap kontributor
                    $formData = json_decode($contributor->form_data, true);

                    // Kumpulkan formData untuk setiap kontributor
                    $formDataSets[] = $formData;
                }

                // Gabungkan semua formData menggunakan fungsi mergeFormData
                $mergedFormData = $this->mergeFormData($formDataSets);

                // Simpan hasil gabungan sesuai dengan contributor_type
                if ($type === 'manager') {
                    $contributorManagerContent = $mergedFormData;
                } elseif ($type === 'peers') {
                    $combinedPeersData = $mergedFormData;
                } elseif ($type === 'subordinate') {
                    $combinedSubData = $mergedFormData;
                }
            }

            $employeeData = $datas->first()->employee;

            // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor
            if (!empty($contributorManagerContent)) {
                $formDataManager = $this->combineFormData($contributorManagerContent, $goalData, 'manager', $employeeData);
            } else {
                $formDataManager = [];  // or null, depending on your needs
            }
            if (!empty($combinedPeersData)) {
                $formDataPeers = $this->combineFormData($combinedPeersData, $goalData, 'peers', $employeeData);
            } else {
                $formDataPeers = [];  // or null, depending on your needs
            }
            if (!empty($combinedSubData)) {
                $formDataSub = $this->combineFormData($combinedSubData, $goalData, 'subordinate', $employeeData);
            } else {
                $formDataSub = [];  // or null, depending on your needs
            }
            
            $formData = $this->combineFormData($appraisalData, $goalData, 'employee', $employeeData);
            
            $suggestedKpi = ($formDataManager['totalKpiScore'] ?? 0) 
            + ($formDataPeers['totalKpiScore'] ?? 0) 
            + ($formDataSub['totalKpiScore'] ?? 0);
            
            $suggestedCulture = ($formData['cultureAverageScore'] ?? 0) 
            + ($formDataManager['cultureAverageScore'] ?? 0) 
            + ($formDataPeers['cultureAverageScore'] ?? 0) 
            + ($formDataSub['cultureAverageScore'] ?? 0);

            $suggestedLeadership = ($formData['leadershipAverageScore'] ?? 0) 
            + ($formDataManager['leadershipAverageScore'] ?? 0) 
            + ($formDataPeers['leadershipAverageScore'] ?? 0) 
            + ($formDataSub['leadershipAverageScore'] ?? 0);


            $suggestedRating = $suggestedKpi + $suggestedCulture + $suggestedLeadership;
            return $suggestedRating;

        }catch (\Exception $e) {
            // Log the error and return an appropriate message or value
            Log::error('Error calculating suggested rating: ' . $e->getMessage());
            return 0;  // You can also return null or any fallback value as needed
        }

    }

    public function convertRating(float $value): ?string
    {
        $condition = MasterRating::where('rating_group_name', 'ratings')
        ->where('min_range', '<=', $value)
        ->where('max_range', '>=', $value)
        ->orderBy('min_range', 'desc')
        ->first();
        
        return $condition ? $condition->parameter : null;
    }

    // Function to handle approval process
    // public function checkCalibrator($employee, $approver)
    // {
    //     $calibrator = Calibration::where('employee_id', $employee)->where('approver_id', $approver)->first();

    //     $layer = ApprovalLayerAppraisal::where('employee_id', $employee)->where('layer_type', 'calibrator')->orderBy('layer', 'asc')->first();

    //     if ($calibrator) {
    //         $current_approver = ApprovalLayerAppraisal::select('approver_id')->where('approver_id', $approver)
    //                         ->where('layer_type', 'calibrator')
    //                         ->orderBy('layer', 'asc')
    //                         ->first();
    //     }else{
    //         $current_approver = Employee::select('employee_id', 'fullname')->where('employee_id', $employee);
    //     }

    //     return $current_approver ? [
    //         'current_approver_id' => $currentLayer->approver_id,
    //         'next_approver_id' => $nextLayer->approver_id,
    //         'layer' => $nextLayer->layer,
    //     ] : null; // null means finish the calibrator layer.

    // }

    public function processApproval($employee, $approver)
    {
        $currentLayer = ApprovalLayerAppraisal::where('employee_id', $employee)
                        ->where('approver_id', $approver)
                        ->where('layer_type', 'calibrator')
                        ->orderBy('layer', 'asc')
                        ->first();

        if ($currentLayer) {
            // Find the next approver in the sequence
            $nextLayer = ApprovalLayerAppraisal::where('employee_id', $employee)
                            ->where('layer', '>', $currentLayer->layer)
                            ->where('layer_type', 'calibrator')
                            ->orderBy('layer', 'asc')
                            ->first();
        }

        return $nextLayer ? [
            'current_approver_id' => $currentLayer->approver_id,
            'next_approver_id' => $nextLayer->approver_id,
            'layer' => $nextLayer->layer,
        ] : null; // null means finish the calibrator layer.

    }

    public function ratingValue($employee, $approver, $period)
    {
        $rating = Calibration::select('appraisal_id','employee_id', 'approver_id', 'rating', 'status', 'period')
                        ->where('employee_id', $employee)
                        ->where('approver_id', $approver)
                        ->where('status', 'Approved')
                        ->where('period', $period)
                        ->first();

        return $rating ? $rating->rating : null; // null means finish the calibrator layer.

    }

    public function ratingAllowedCheck($employeeId)
    {
        // Cari data pada ApprovalLayerAppraisal berdasarkan employee_id
        $approvalLayerAppraisals = ApprovalLayerAppraisal::with(['approver', 'employee'])->where('employee_id', $employeeId)->where('layer_type', '!=', 'calibrator')->get();

        
        // Simpan data yang tidak ada di AppraisalContributor
        $notFoundData = [];
        
        foreach ($approvalLayerAppraisals as $approvalLayer) {

            $review360 = json_decode($approvalLayer->employee->access_menu, true);

            // Cek apakah kombinasi employee_id dan approver_id tidak ada di AppraisalContributor
            $appraisalContributor = AppraisalContributor::where('employee_id', $approvalLayer->employee_id)
                                                        ->where('contributor_id', $approvalLayer->approver_id)
                                                        ->first();
            
            // Jika tidak ditemukan, tambahkan ke notFoundData
            if (!$appraisalContributor && $review360['review360']) {
                $notFoundData[] = [
                    'employee_id'  => $approvalLayer->employee_id,
                    'approver_id'  => $approvalLayer->approver_id,
                    'approver_name'  => $approvalLayer->approver->fullname,
                    'layer_type'   => $approvalLayer->layer_type,
                ];
            }
        }
        
        // Jika ada data yang tidak ditemukan di AppraisalContributor, kembalikan datanya
        if (!empty($notFoundData)) {
            return [
                'status' => false,
                'message' => '360 Review incomplete process',
                'data' => $notFoundData
            ];
        }
        
        // Jika semua data ada, kembalikan pesan data lengkap
        return [
            'status' => true,
            'message' => '360 Review completed'
        ];
    }
    
}