<?php

namespace App\Services;

use App\Models\AppraisalContributor;
use App\Models\ApprovalLayer;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\FormGroupAppraisal;
use App\Models\KpiUnits;
use App\Models\MasterCalibration;
use App\Models\MasterRating;
use App\Models\MasterWeightage;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

use stdClass;

class AppService
{
    public function formGroupAppraisal($employee_id, $form_name)
    {
        $employee = EmployeeAppraisal::select('employee_id', 'group_company', 'job_level', 'company_name', 'work_area_code')->where('employee_id', $employee_id)->first();
        
        $datas = FormGroupAppraisal::with(['formAppraisals', 'rating'])->where('name', $form_name)->get();

        $data = json_decode($datas, true);

        $criteria = [
            "job_level" => $employee->job_level,
            "work_area" => $employee->work_area_code,
            "group_company" => $employee->group_company,
        ];

        $filteredData = $this->filterByRestrict($data, $criteria);
        
        return [
            'status' => 'success',
            'data' => array_values($filteredData)[0] ?? []
        ];
    }

    private function filterByRestrict($data, $criteria) {
        return array_filter($data, function ($item) use ($criteria) {
            $restrict = $item['restrict'];
    
            // Check each criterion
            $jobLevelMatch = empty($restrict['job_level']) || 
            (isset($criteria['job_level']) && in_array($criteria['job_level'], $restrict['job_level']));
            $workAreaMatch = empty($restrict['work_area']) || 
            (isset($criteria['work_area']) && in_array($criteria['work_area'], $restrict['work_area']));
            $companyMatch = empty($restrict['company_name']) || 
                            (isset($criteria['company_name']) && in_array($criteria['company_name'], $restrict['company_name']));
            $groupCompanyMatch = empty($restrict['group_company']) || 
            (isset($criteria['group_company']) && in_array($criteria['group_company'], $restrict['group_company']));
    
            // Return true if all criteria match
            return $jobLevelMatch && $workAreaMatch && $companyMatch && $groupCompanyMatch;
        });
    }

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
        // Ensure inputs are numeric
        if (!is_numeric($achievement) || !is_numeric($target)) {
            throw new Exception('Achievement and target must be numeric');
        }
    
        // Convert to floats for consistent handling
        $achievement = floatval($achievement);
        $target = floatval($target);
    
        switch (strtolower($type)) {
            case 'higher better':
                if ($target == 0) {
                    return $achievement > 0 ? 100 : 0;
                }
                
                return ($achievement / $target) * 100;
    
            case 'lower better':
                if ($target == 0) {
                    return $achievement <= 0 ? 100 : 0;
                }
                if ($achievement <= 0) {
                    return 100;
                }

                return (2 - ($achievement / $target)) * 100;
    
            case 'exact value':
                $epsilon = 1e-6; // Adjust based on required precision
                return abs($achievement - $target) < $epsilon ? 100 : 0;
    
            default:
                throw new Exception('Invalid type'. $type);
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

    public function combineFormData($appraisalData, $goalData, $typeWeightage360, $employeeData, $period) {

        $totalKpiScore = 0; // Initialize the total score
        $totalCultureScore = 0; // Initialize the total score
        $totalLeadershipScore = 0; // Initialize the total score
        $cultureAverageScore = 0; // Initialize Culture average score
        $leadershipAverageScore = 0; // Initialize Culture average score
        
        $jobLevel = $employeeData->job_level;
        
        $appraisalDatas = $appraisalData;

        if (!empty($appraisalDatas['formData']) && is_array($appraisalDatas['formData'])) {
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
        } else {
            // Handle the case where formData is null or not an array
            $appraisalDatas['formData'] = []; // Optionally, set to an empty array
        }
        
        $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $period)->first();
        
        $weightageContent = json_decode($weightageData->form_data, true);
        
        $kpiWeightage = 0;
        $cultureWeightage = 0;
        $leadershipWeightage = 0;

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
                        case 'KPI':
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

        $appraisalDatas['cultureWeightage'] = $cultureWeightage; // get KPI Final Score
        $appraisalDatas['leadershipWeightage'] = $leadershipWeightage; // get KPI Final Score
        
        // Add the total scores to the appraisalData
        $appraisalDatas['totalKpiScore'] = round($totalKpiScore * $kpiWeightage / 100 , 2); // get KPI Final Score
        $appraisalDatas['totalCultureScore'] = round($cultureAverageScore, 2); // get KPI Final Score
        $appraisalDatas['totalLeadershipScore'] = round($leadershipAverageScore, 2); // get KPI Final Score
        $appraisalDatas['cultureScore360'] = $cultureAverageScore * $cultureWeightage360 / 100; // get KPI Final Score
        $appraisalDatas['leadershipScore360'] = $leadershipAverageScore * $leadershipWeightage360 / 100; // get KPI Final Score
        $appraisalDatas['cultureAverageScore'] = ($cultureAverageScore * $cultureWeightage / 100) * $appraisalDatas['cultureWeightage360']; // get Culture Average Score
        $appraisalDatas['leadershipAverageScore'] = ($leadershipAverageScore * $leadershipWeightage / 100) * $appraisalDatas['leadershipWeightage360']; // get Leadership Average Score
        
        $appraisalDatas['kpiScore'] = $totalKpiScore; // get KPI Final Score
        $appraisalDatas['cultureScore'] = $cultureAverageScore * $cultureWeightage / 100; // get KPI Final Score
        $appraisalDatas['leadershipScore'] = $leadershipAverageScore  * $leadershipWeightage / 100; // get KPI Final Score

        $scores = [$totalKpiScore,$cultureAverageScore,$leadershipAverageScore];
        // get KPI Final Score
        // $appraisalDatas['totalScore'] =  round(array_sum($scores) / count($scores) ,2); // Old
        $appraisalDatas['selfTotalScore'] =  $appraisalDatas['totalKpiScore'] + $appraisalDatas['cultureScore'] + $appraisalDatas['leadershipScore']; // Update
        $appraisalDatas['totalScore'] =  $appraisalDatas['totalKpiScore'] + $appraisalDatas['totalCultureScore'] + $appraisalDatas['totalLeadershipScore']; // Update

        $appraisalDatas['contributorRating'] = $appraisalDatas['totalKpiScore'] + $appraisalDatas['cultureAverageScore'] + $appraisalDatas['leadershipAverageScore']; // old
        $appraisalDatas['contributorRating'] = $appraisalDatas['totalKpiScore'] + $appraisalDatas['totalCultureScore'] + $appraisalDatas['totalLeadershipScore']; // update
    // dd($appraisalDatas);
        return $appraisalDatas;
    }
    
    public function combineSummaryFormData($appraisalData, $goalData, $employeeData, $period) {

        $totalKpiScore = 0; // Initialize the total score
        $totalCultureScore = 0; // Initialize the total score
        $totalLeadershipScore = 0; // Initialize the total score
        $cultureAverageScore = 0; // Initialize Culture average score
        $leadershipAverageScore = 0; // Initialize Culture average score

        $jobLevel = $employeeData->job_level;

        $result = $this->averageScores($appraisalData['calculated_data']);

        foreach ($result as $appraisalDatas) {

            

            if (!empty($appraisalDatas['formData']) && is_array($appraisalDatas['formData'])) {
                // Initialize the culture and leadership scores for this contributor type
                $cultureAverageScore = 0;
                $leadershipAverageScore = 0;
        
                foreach ($appraisalDatas['formData'] as &$form) {
                    if ($form['formName'] === "Culture") {
                        // Calculate average score for Culture form
                        $cultureAverageScore = $this->averageScore($form);
                    } elseif ($form['formName'] === "Leadership") {
                        // Calculate average score for Leadership form
                        $leadershipAverageScore = $this->averageScore($form);
                    }
                }
        
                // Sum the culture and leadership scores across all contributor types
                $totalCultureScore += $cultureAverageScore;
                $totalLeadershipScore += $leadershipAverageScore;
            } else {
                // Handle the case where formData is null or not an array
                $appraisalDatas['formData'] = []; // Optionally, set to an empty array
            }
            
            $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')
                        ->where('period', $period)
                        ->first();
            
            $weightageContent = json_decode($weightageData->form_data, true);
    
            $kpiWeightage = 0;
            $cultureWeightage = 0;
            $leadershipWeightage = 0;
    
            foreach ($weightageContent as $item) {
                if (in_array($jobLevel, $item['jobLevel'])) {
                    foreach ($item['competencies'] as $competency) {
                        $employeeWeightage = 0;
    
                        if (isset($competency['weightage360'])) {
                            foreach ($competency['weightage360'] as $weightage360) {
                                if (isset($weightage360[$appraisalDatas['contributor_type']])) {
                                    $employeeWeightage += $weightage360[$appraisalDatas['contributor_type']];
                                }
                            }
                        }
    
                        switch ($competency['competency']) {
                            case 'KPI':
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
        }

        $appraisalDatas = $appraisalData['summary'];
        
        if (!empty($appraisalDatas['formData']) && is_array($appraisalDatas['formData'])) {
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
                }
            }
        } else {
            // Handle the case where formData is null or not an array
            $appraisalDatas['formData'] = []; // Optionally, set to an empty array
        }
        
        $appraisalDatas['kpiWeightage360'] = $kpiWeightage360; // get KPI 360 weightage
        $appraisalDatas['cultureWeightage360'] = $cultureWeightage360 / 100; // get Culture 360 weightage
        $appraisalDatas['leadershipWeightage360'] = $leadershipWeightage360 / 100; // get Leadership 360 weightage
        
        // Add the total scores to the appraisalData
        $appraisalDatas['totalKpiScore'] = round($totalKpiScore * $kpiWeightage / 100 , 2); // get KPI Final Score
        $appraisalDatas['totalCultureScore'] = round($totalCultureScore, 2); // get KPI Final Score
        // $appraisalDatas['totalCultureScore'] = round($cultureAverageScore * $cultureWeightage / 100 , 2); // get KPI Final Score
        $appraisalDatas['totalLeadershipScore'] = round($totalLeadershipScore, 2); // get KPI Final Score
        // $appraisalDatas['totalLeadershipScore'] = round($leadershipAverageScore * $leadershipWeightage / 100 , 2); // get KPI Final Score
        $appraisalDatas['cultureScore360'] = $cultureAverageScore * $cultureWeightage360 / 100; // get KPI Final Score
        $appraisalDatas['leadershipScore360'] = $leadershipAverageScore * $leadershipWeightage360 / 100; // get KPI Final Score
        $appraisalDatas['cultureAverageScore'] = ($cultureAverageScore * $cultureWeightage / 100) * $appraisalDatas['cultureWeightage360']; // get Culture Average Score
        $appraisalDatas['leadershipAverageScore'] = ($leadershipAverageScore * $leadershipWeightage / 100) * $appraisalDatas['leadershipWeightage360']; // get Leadership Average Score
        
        $appraisalDatas['kpiScore'] = $totalKpiScore; // get KPI Final Score
        $appraisalDatas['cultureScore'] = $cultureAverageScore; // get KPI Final Score
        $appraisalDatas['leadershipScore'] = $leadershipAverageScore; // get KPI Final Score

        $scores = [$totalKpiScore,$cultureAverageScore,$leadershipAverageScore];
        // get KPI Final Score
        // $appraisalDatas['totalScore'] =  round(array_sum($scores) / count($scores) ,2); // Old
        $appraisalDatas['totalScore'] =  $appraisalDatas['totalKpiScore'] + $appraisalDatas['totalCultureScore'] + $appraisalDatas['totalLeadershipScore']; // Update

        $appraisalDatas['contributorRating'] = $appraisalDatas['totalKpiScore'] + $appraisalDatas['cultureAverageScore'] + $appraisalDatas['leadershipAverageScore']; // old
        $appraisalDatas['contributorRating'] = $appraisalDatas['totalKpiScore'] + $appraisalDatas['totalCultureScore'] + $appraisalDatas['totalLeadershipScore']; // update
    
        return $appraisalDatas;
    }

    function averageScores(array $data): array
    {
        // Group data by contributor_type
        $groupedData = [];
        foreach ($data as $entry) {
            $contributorType = $entry['contributor_type'];
            $groupedData[$contributorType][] = $entry;
        }

        $result = [];
        foreach ($groupedData as $contributorType => $entries) {
            // Clone the structure from the first entry
            $mergedEntry = $entries[0];
            $mergedEntry['formData'] = [];

            foreach ($entries[0]['formData'] as $index => $formData) {
                $mergedFormData = $formData;
                $mergedFormData[0] = [];

                // Ensure the current formData is properly structured
                if (!isset($formData[0]) || !is_array($formData[0])) {
                    continue;
                }

                // Calculate averages for all scores in the same index
                foreach ($formData[0] as $scoreIndex => $scoreData) {
                    // Ensure the scoreData has the expected structure
                    if (!isset($scoreData['score']) || !is_numeric($scoreData['score'])) {
                        continue;
                    }

                    $totalScore = 0;
                    $count = 0;

                    foreach ($entries as $entry) {
                        // Validate structure before accessing
                        if (isset($entry['formData'][$index][0][$scoreIndex]['score']) &&
                            is_numeric($entry['formData'][$index][0][$scoreIndex]['score'])) {
                            $totalScore += $entry['formData'][$index][0][$scoreIndex]['score'];
                            $count++;
                        }
                    }

                    // Avoid division by zero
                    if ($count > 0) {
                        $mergedFormData[0][$scoreIndex] = [
                            'score' => $totalScore / $count,
                        ];
                    }
                }

                // Push merged form data
                $mergedEntry['formData'][] = $mergedFormData;
            }

            $result[] = $mergedEntry;
        }

        return $result;
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

    function suggestedRating($id, $formId)
    {
        try {

            $datasQuery = AppraisalContributor::with(['employee'])->where('appraisal_id', $formId);
                $datas = $datasQuery->get();

                $checkSnapshot = ApprovalSnapshots::where('form_id', $formId)->where('created_by', $datas->first()->employee->id)
                    ->orderBy('created_at', 'desc');

                // Check if `datas->first()->employee->id` exists
                if ($checkSnapshot) {
                    $query = $checkSnapshot;
                }else{
                    $query = ApprovalSnapshots::where('form_id', $formId)
                    ->orderBy('created_at', 'asc');
                }
                
                $employeeForm = $query->first();

                $data = [];
                $appraisalDataCollection = [];
                $goalDataCollection = [];

                $formGroupContent = $this->formGroupAppraisal($datas->first()->employee_id, 'Appraisal Form');
                
                if (!$formGroupContent) {
                    $appraisalForm = ['data' => ['formData' => []]];
                } else {
                    $appraisalForm = $formGroupContent;
                }
                
                $cultureData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
                $leadershipData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];
                
                
                if($employeeForm){

                    // Create data item object
                    $dataItem = new stdClass();
                    $dataItem->request = $employeeForm;
                    $dataItem->name = $employeeForm->name;
                    $dataItem->goal = $employeeForm->goal;
                    $data[] = $dataItem;
    
                    // Get appraisal form data for each record
                    $appraisalData = [];
                    
                    if ($employeeForm->form_data) {
                        $appraisalData = json_decode($employeeForm->form_data, true);
                        $contributorType = $employeeForm->contributor_type;
                        $appraisalData['contributor_type'] = 'employee';
                    }
    
                    // Get goal form data for each record
                    $goalData = [];
                    if ($employeeForm->goal && $employeeForm->goal->form_data) {
                        $goalData = json_decode($employeeForm->goal->form_data, true);
                        $goalDataCollection[] = $goalData;
                    }
                    
                    // Combine the appraisal and goal data for each contributor
                    $employeeData = $employeeForm->employee; // Get employee data
            
                    $formData[] = $appraisalData;

                }
                
                foreach ($datas as $request) {

                    // Create data item object
                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->name = $request->name;
                    $dataItem->goal = $request->appraisal->goal;
                    $data[] = $dataItem;
                    
                    // Get appraisal form data for each record
                    $appraisalData = [];

                    if ($request->form_data) {
                        $appraisalData = json_decode($request->form_data, true);
                        $contributorType = $request->contributor_type;
                        $appraisalData['contributor_type'] = $contributorType;
                    }

                    // Get goal form data for each record
                    $goalData = [];
                    if ($request->appraisal->goal && $request->appraisal->goal->form_data) {
                        $goalData = json_decode($request->appraisal->goal->form_data, true);
                        $goalDataCollection[] = $goalData;
                    }
                    
                    // Combine the appraisal and goal data for each contributor
                    $employeeData = $request->employee; // Get employee data
            
                    $formData[] = $appraisalData;

                }

                $jobLevel = $employeeData->job_level;

                $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $request->period)->first();
                            
                $weightageContent = json_decode($weightageData->form_data, true);
                
                $result = $this->appraisalSummary($weightageContent, $formData, $employeeData->employee_id, $jobLevel);

                // $formData = $this->combineFormData($result['summary'], $goalData, $result['summary']['contributor_type'], $employeeData, $request->period);
                
                
                $formData = $this->combineSummaryFormData($result, $goalData, $employeeData, $request->period);

                if (isset($formData['totalKpiScore'])) {
                    $formData['kpiScore'] = round($formData['kpiScore'], 2);
                    $formData['cultureScore'] = round($formData['cultureScore'], 2);
                    $formData['leadershipScore'] = round($formData['leadershipScore'], 2);
                }

                foreach ($formData['formData'] as &$form) {
                    if ($form['formName'] === 'Leadership') {
                        foreach ($leadershipData as $index => $leadershipItem) {
                            foreach ($leadershipItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $leadershipItem['title'];
                        }
                    }
                    
                    if ($form['formName'] === 'Culture') {
                        foreach ($cultureData as $index => $cultureItem) {
                            foreach ($cultureItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $cultureItem['title'];
                        }
                    }
                
                }       
                
                $appraisalData = $formData['totalScore'];
            
            return $appraisalData;

        }catch (\Exception $e) {
            // Log the error and return an appropriate message or value
            Log::error('Error calculating suggested rating: ' . $e->getMessage());
            return 0;  // You can also return null or any fallback value as needed
        }

    }

    public function convertRating(float $value, $formID): ?string
    {
        $formGroup = MasterCalibration::where('id_calibration_group', $formID)->first();
        
        $condition = null;
        
        $roundedValue = (int) round($value);

        if ($value == 0) {
            // If value is 0, get the record with the smallest value
            $condition = MasterRating::orderBy('value', 'asc')->first();
        } else {
            // Otherwise, proceed with the original query logic
            $condition = MasterRating::where(function ($query) use ($formGroup, $value) {
                $query->where('id_rating_group', $formGroup->id_rating_group)
                      ->where('min_range', '<=', $value)
                      ->where('max_range', '>=', $value);
            })
            ->orWhere(function ($query) use ($formGroup, $roundedValue) {
                        $query->where('id_rating_group', $formGroup->id_rating_group)
                              ->where('min_range', 0)
                              ->where('max_range', 0)
                              ->where('value', $roundedValue); // Use rounded value here
                    })
                    ->orderBy('min_range', 'desc')
                    ->first();
        }

        return $condition ? $condition->parameter : null;
    }

    public function processApproval($employee, $approver)
    {
        $currentLayer = ApprovalLayerAppraisal::where('employee_id', $employee)
                        ->where('approver_id', $approver)
                        ->where('layer_type', 'calibrator')
                        ->orderBy('layer', 'asc')
                        ->first();

        $nextLayer = [];
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

    // public function ratingValue($employee, $approver, $period)
    // {

    //     $rating = Calibration::with(['masterCalibration'])
    //                     ->where('employee_id', $employee)
    //                     ->where('approver_id', $approver)
    //                     ->where('status', 'Approved')
    //                     ->where('period', $period)
    //                     ->first();
                        
    //     $id_rating = $rating->masterCalibration->first()->id_rating_group;
        
    //     $ratings = MasterRating::select('parameter', 'value')
    //                 ->where('id_rating_group', $id_rating)
    //                 ->get();
        
    //     $ratingMap = $ratings->pluck('parameter', 'value')->toArray();

    //     $convertedValue = $ratingMap[$rating->rating] ?? null;

    //     return $rating ? $convertedValue : null; // null means finish the calibrator layer.

    // }

    public function ratingAllowedCheck($employeeId)
    {
        // Cari data pada ApprovalLayerAppraisal berdasarkan employee_id
        $approvalLayerAppraisals = ApprovalLayerAppraisal::with(['approver', 'employee'])->where('employee_id', $employeeId)->where('layer_type', '!=', 'calibrator')->get();
        
        // Simpan data yang tidak ada di AppraisalContributor
        $notFoundData = [];
        
        foreach ($approvalLayerAppraisals as $approvalLayer) {
            
            $review360 = json_decode($approvalLayer->employee->access_menu, true);

            if (!array_key_exists('review360', $review360)) {
                $review360['review360'] = 0;
            }
            
            // Cek apakah kombinasi employee_id dan approver_id tidak ada di AppraisalContributor
            $appraisalContributor = AppraisalContributor::where('employee_id', $approvalLayer->employee_id)
                                                        ->where('contributor_id', $approvalLayer->approver_id)
                                                        ->first();
            
            // Jika tidak ditemukan, tambahkan ke notFoundData
            if (!$appraisalContributor && !$review360['review360']) {
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

    public function goalPeriod()
    {
        $today = Carbon::today()->toDateString();

        $period = Schedule::where('event_type', 'goals')
                        ->orderByDesc('id')
                        ->value('schedule_periode');

        return $period;
    }

    public function goalActivePeriod()
    {
        $today = Carbon::today()->toDateString();

        $period = Schedule::where('event_type', 'goals')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->orderByDesc('id')
                        ->value('schedule_periode');
        return $period;
    }

    public function appraisalPeriod()
    {
        $today = Carbon::today()->toDateString();

        $period = Schedule::where('event_type', 'masterschedulepa')
                        ->orderByDesc('id')
                        ->value('schedule_periode');
        return $period;
    }

    public function appraisalActivePeriod()
    {
        $today = Carbon::today()->toDateString();

        $period = Schedule::where('event_type', 'masterschedulepa')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->orderByDesc('id')
                        ->value('schedule_periode');
        return $period;
    }

    public function getDataByName($data, $name) {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    public function getNotificationCountsGoal($user)
    {
        $period = $this->goalPeriod();
        
        $category = 'Goals';

        $tasks = ApprovalRequest::where([
            ['current_approval_id', $user],
            ['period', $period],
            ['category', $category],
            ['status', 'Pending'],
        ])
        ->whereHas('goal', function ($query) {
            $query->where('form_status', 'Submitted');
        })
        ->whereHas('employee', function ($query) {
            $query->whereNull('deleted_at');
        })
        ->get();
    
        $isApprover = $tasks->count();
        
        // Output the result
        return $isApprover;
    }

    public function getNotificationCountsAppraisal($user)
    {
        $period = $this->appraisalPeriod();

        // Count for teams notifications
        $dataTeams = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) use ($user, $period) {
            $query->where('contributor_id', $user)->where('period', $period);
        }, 'goal' => function($query) use ($period) {
            $query->where('period', $period);
        }])
        ->where('approver_id', $user)
        ->where('layer_type', 'manager')
        ->whereHas('employee', function ($query) {
            $query->where(function($q) {
                $q->whereRaw('json_valid(access_menu)')
                  ->whereJsonContains('access_menu', ['createpa' => 1]);
            });
        })
        ->get();

        $notifTeams = $dataTeams->filter(function ($item) {
            return $item->contributors->isEmpty() && $item->goal->isNotEmpty();
        })->count();
        
        // Count for 360 appraisal notifications
        $data360 = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) use ($user, $period) {
            $query->where('contributor_id', $user)->where('period', $period);
        }, 'appraisal' => function($query) use ($period) {
            $query->where('period', $period);
        }])
            ->where('approver_id', $user)
            ->whereNotIn('layer_type', ['manager', 'calibrator'])
            ->get()
            ->filter(function ($item) {
                return $item->appraisal != null && $item->contributors->isEmpty();
            });
        
        $notif360 = $data360->count();

        $notifData = $notifTeams + $notif360;
        
        return $notifData;
    }

    function appraisalSummary($weightages, $formData, $employeeID, $jobLevel) {

        $calculatedFormData = [];

        $checkLayer = ApprovalLayerAppraisal::where('employee_id', $employeeID)
        ->where('layer_type', '!=', 'calibrator')
        ->selectRaw('layer_type, COUNT(*) as count')
        ->groupBy('layer_type')
        ->get();

        $layerCounts = $checkLayer->pluck('count', 'layer_type')->toArray();

        $managerCount = $layerCounts['manager'] ?? 0;
        $peersCount = $layerCounts['peers'] ?? 0;
        $subordinateCount = $layerCounts['subordinate'] ?? 0;

        $calculatedFormData = []; // Initialize result array

        // Loop through $formData first to structure results by formGroupName and contributor_type
        foreach ($formData as $data) {
            $contributorType = $data['contributor_type'];
            $formGroupName = $data['formGroupName'];
            $formDataWithCalculatedScores = []; // Array to store calculated scores for the group

            foreach ($data['formData'] as $form) {
                $formName = $form['formName'];
                $calculatedForm = ["formName" => $formName];
                if ($formName === "KPI") {
                    // Directly copy KPI achievements
                    foreach ($form as $key => $achievement) {
                        if (is_numeric($key)) {
                            $calculatedForm[$key] = $achievement;
                        }
                    }
                } else {
                    // Process other forms
                    foreach ($weightages as $item) {
                        if(in_array($jobLevel, $item['jobLevel'])){
                            foreach ($item['competencies'] as $competency) {
                                if ($competency['competency'] == $formName) {
                                    // Handle weightage360
                                    $weightage360 = 0;
    
                                    if (isset($competency['weightage360'])) {
                                        // Extract weightages for each type
                                        $weightageValues = collect($competency['weightage360'])->flatMap(function ($weightage) {
                                            return $weightage;
                                        });
    
                                        $weightage360 = $weightageValues[$contributorType] ?? 0;
    
                                        if($contributorType == 'manager'){
                                            if ($subordinateCount > 0) {
                                                $weightage360 ?? 0;
                                            }
                                            // Adjust weightages
                                            if ($subordinateCount == 0) {
                                                $weightage360 += $weightageValues['subordinate'] ?? 0;
                                            }
                                            if ($peersCount == 0) {
                                                $weightage360 += $weightageValues['peers'] ?? 0;
                                            }
                                            // if ($subordinateCount == 0 && $peersCount == 0) {
                                            //     $weightage360 += ($weightageValues['subordinate'] ?? 0) + ($weightageValues['peers'] ?? 0);
                                            // }
                                        }

                                    }
    
                                    // Calculate weighted scores
                                    foreach ($form as $key => $scores) {
                                        if (is_numeric($key)) {
                                            $calculatedForm[$key] = [];
                                            foreach ($scores as $scoreData) {
                                                $score = $scoreData['score'];
                                                $weightedScore = ($score * ($weightage360 / 100) * ($competency['weightage'] / 100));
                                                $calculatedForm[$key][] = ["score" => $weightedScore];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $formDataWithCalculatedScores[] = $calculatedForm;
            }
            
            $calculatedFormData[] = [
                "formGroupName" => $formGroupName,
                "formData" => $formDataWithCalculatedScores,
                "contributor_type" => $contributorType
            ];

        }
        
        // Second part: Calculate summary averages
        $averages = [];

        // Iterate through each contributor's data
        foreach ($calculatedFormData as $contributorData) {

            $contributorType = $contributorData['contributor_type'];
            
            foreach ($contributorData['formData'] as $form) {
                $formName = $form['formName'];
                
                if ($formName === 'KPI') {
                    // Store KPI values (only from manager)
                    if ($contributorType === 'manager') {
                        foreach ($form as $key => $value) {
                            if (is_numeric($key)) {
                                // Initialize if not already set
                                if (!isset($summedScores[$formName][$key])) {
                                    $summedScores[$formName][$key] = ["achievement" => $value['achievement']];
                                }
                            }
                        }
                    }
                } else {

                    // Process forms like Culture and Leadership
                    foreach ($form as $key => $values) {

                        if (is_numeric($key)) {
                            // Initialize if not already set
                            if (!isset($summedScores[$formName][$key])) {
                                $summedScores[$formName][$key] = [];
                            }

                            // Apply weightage if peers or subordinate count is non-zero
                            foreach ($values as $index => $scoreData) {
                                // Ensure the array exists for this index
                                if (!isset($summedScores[$formName][$key][$index])) {
                                    $summedScores[$formName][$key][$index] = ["score" => 0];
                                }
                                // Accumulate the score
                                $summedScores[$formName][$key][$index]['score'] += $scoreData['score'];
                            }
                            
                        }
                        // if (is_numeric($key)) {
                        //     // Initialize if not already set
                        //     if (!isset($summedScores[$formName][$key])) {
                        //         $summedScores[$formName][$key] = [];
                        //     }

                        //     // if ($peersCount == 0 || $subordinateCount == 0) {
                        //     //     // Sum scores directly without weightage
                        //     //     $totalScore = 0;
                        //     //     $scoreCount = count($values);
                    
                        //     //     foreach ($values as $index => $scoreData) {
                        //     //         $totalScore += $scoreData['score'];
                        //     //     }
                    
                        //     //     // Calculate the average score
                        //     //     $averageScore = $scoreCount > 0 ? $totalScore / $scoreCount : 0;
                    
                        //     //     // Store the average score at this index
                        //     //     $summedScores[$formName][$key][] = ["score" => $averageScore];
                        //     // } else {
                        //         // Apply weightage if peers or subordinate count is non-zero
                        //         foreach ($values as $index => $scoreData) {
                        //             // Ensure the array exists for this index
                        //             if (!isset($summedScores[$formName][$key][$index])) {
                        //                 $summedScores[$formName][$key][$index] = ["score" => 0];
                        //             }
                        //             // Accumulate the score
                        //             $summedScores[$formName][$key][$index]['score'] += $scoreData['score'];
                        //         }
                        //     // }
                            
                        // }
                    }
                }
            }
        }

        // Format the summary response
        $summary = [
            "formGroupName" => "Appraisal Form",
            "formData" => [],
            "contributor_type" => "summary"
        ];

        // Add KPI first if exists
        if (isset($summedScores['KPI'])) {
            $kpiForm = [
                "formName" => "KPI"
            ];
            foreach ($summedScores['KPI'] as $key => $value) {
                $kpiForm[$key] = $value; // Include KPI data in the summary
            }
            $summary['formData'][] = $kpiForm; // Add KPI to the summary
        }

        // Add Culture and Leadership
        foreach (['Culture', 'Leadership'] as $formName) {
            if (isset($summedScores[$formName])) {
                $form = [
                    "formName" => $formName
                ];
                foreach ($summedScores[$formName] as $key => $scores) {
                    $form[$key] = $scores; // Include Culture or Leadership data in the summary
                }
                $summary['formData'][] = $form; // Add Culture or Leadership to the summary
            }
        }
        
        // Return both calculated data and summary
        return [
            'calculated_data' => $calculatedFormData,
            'summary' => $summary
        ];
    }

    function appraisalSummaryWithout360Calculation($weightages, $formData, $employeeID, $jobLevel) {

        $calculatedFormData = [];

        $checkLayer = ApprovalLayerAppraisal::where('employee_id', $employeeID)
        ->where('layer_type', '!=', 'calibrator')
        ->selectRaw('layer_type, COUNT(*) as count')
        ->groupBy('layer_type')
        ->get();

        $layerCounts = $checkLayer->pluck('count', 'layer_type')->toArray();

        $managerCount = $layerCounts['manager'] ?? 0;
        $peersCount = $layerCounts['peers'] ?? 0;
        $subordinateCount = $layerCounts['subordinate'] ?? 0;

        $calculatedFormData = []; // Initialize result array

        // Loop through $formData first to structure results by formGroupName and contributor_type
        foreach ($formData as $data) {
            $contributorType = $data['contributor_type'];
            $formGroupName = $data['formGroupName'];
            $formDataWithCalculatedScores = []; // Array to store calculated scores for the group

            foreach ($data['formData'] as $form) {
                $formName = $form['formName'];
                $calculatedForm = ["formName" => $formName];
                if ($formName === "KPI") {
                    // Directly copy KPI achievements
                    foreach ($form as $key => $achievement) {
                        if (is_numeric($key)) {
                            $calculatedForm[$key] = $achievement;
                        }
                    }
                } else {
                    // Process other forms
                    foreach ($weightages as $item) {
                        if(in_array($jobLevel, $item['jobLevel'])){
                            foreach ($item['competencies'] as $competency) {
                                if ($competency['competency'] == $formName) {
                                    // Handle weightage360
                                    $weightage360 = 0;
    
                                    if (isset($competency['weightage360'])) {
                                        // Extract weightages for each type
                                        $weightageValues = collect($competency['weightage360'])->flatMap(function ($weightage) {
                                            return $weightage;
                                        });
    
                                        $weightage360 = $weightageValues[$contributorType] ?? 0;
    
                                        if($contributorType == 'manager'){
                                            if ($subordinateCount > 0) {
                                                $weightage360 ?? 0;
                                            }
                                            // Adjust weightages
                                            if ($subordinateCount == 0) {
                                                $weightage360 += $weightageValues['subordinate'] ?? 0;
                                            }
                                            if ($peersCount == 0) {
                                                $weightage360 += $weightageValues['peers'] ?? 0;
                                            }
                                            // if ($subordinateCount == 0 && $peersCount == 0) {
                                            //     $weightage360 += ($weightageValues['subordinate'] ?? 0) + ($weightageValues['peers'] ?? 0);
                                            // }
                                        }

                                    }
    
                                    // Calculate weighted scores
                                    foreach ($form as $key => $scores) {
                                        if (is_numeric($key)) {
                                            $calculatedForm[$key] = [];
                                            foreach ($scores as $scoreData) {
                                                $score = $scoreData['score'];
                                                $weightedScore = $score;
                                                $calculatedForm[$key][] = ["score" => $weightedScore];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $formDataWithCalculatedScores[] = $calculatedForm;
            }
            
            $calculatedFormData[] = [
                "formGroupName" => $formGroupName,
                "formData" => $formDataWithCalculatedScores,
                "contributor_type" => $contributorType
            ];

        }
        
        // Second part: Calculate summary averages
        $averages = [];

        // Iterate through each contributor's data
        foreach ($calculatedFormData as $contributorData) {

            $contributorType = $contributorData['contributor_type'];
            
            foreach ($contributorData['formData'] as $form) {
                $formName = $form['formName'];
                
                if ($formName === 'KPI') {
                    // Store KPI values (only from manager)
                    if ($contributorType === 'manager') {
                        foreach ($form as $key => $value) {
                            if (is_numeric($key)) {
                                // Initialize if not already set
                                if (!isset($summedScores[$formName][$key])) {
                                    $summedScores[$formName][$key] = ["achievement" => $value['achievement']];
                                }
                            }
                        }
                    }
                } else {

                    // Process forms like Culture and Leadership
                    foreach ($form as $key => $values) {

                        if (is_numeric($key)) {
                            // Initialize if not already set
                            if (!isset($summedScores[$formName][$key])) {
                                $summedScores[$formName][$key] = [];
                            }

                            // Apply weightage if peers or subordinate count is non-zero
                            foreach ($values as $index => $scoreData) {
                                // Ensure the array exists for this index
                                if (!isset($summedScores[$formName][$key][$index])) {
                                    $summedScores[$formName][$key][$index] = ["score" => 0];
                                }
                                // Accumulate the score
                                $summedScores[$formName][$key][$index]['score'] += $scoreData['score'];
                            }
                            
                        }
                        // if (is_numeric($key)) {
                        //     // Initialize if not already set
                        //     if (!isset($summedScores[$formName][$key])) {
                        //         $summedScores[$formName][$key] = [];
                        //     }

                        //     // if ($peersCount == 0 || $subordinateCount == 0) {
                        //     //     // Sum scores directly without weightage
                        //     //     $totalScore = 0;
                        //     //     $scoreCount = count($values);
                    
                        //     //     foreach ($values as $index => $scoreData) {
                        //     //         $totalScore += $scoreData['score'];
                        //     //     }
                    
                        //     //     // Calculate the average score
                        //     //     $averageScore = $scoreCount > 0 ? $totalScore / $scoreCount : 0;
                    
                        //     //     // Store the average score at this index
                        //     //     $summedScores[$formName][$key][] = ["score" => $averageScore];
                        //     // } else {
                        //         // Apply weightage if peers or subordinate count is non-zero
                        //         foreach ($values as $index => $scoreData) {
                        //             // Ensure the array exists for this index
                        //             if (!isset($summedScores[$formName][$key][$index])) {
                        //                 $summedScores[$formName][$key][$index] = ["score" => 0];
                        //             }
                        //             // Accumulate the score
                        //             $summedScores[$formName][$key][$index]['score'] += $scoreData['score'];
                        //         }
                        //     // }
                            
                        // }
                    }
                }
            }
        }

        // Format the summary response
        $summary = [
            "formGroupName" => "Appraisal Form",
            "formData" => [],
            "contributor_type" => "summary"
        ];

        // Add KPI first if exists
        if (isset($summedScores['KPI'])) {
            $kpiForm = [
                "formName" => "KPI"
            ];
            foreach ($summedScores['KPI'] as $key => $value) {
                $kpiForm[$key] = $value; // Include KPI data in the summary
            }
            $summary['formData'][] = $kpiForm; // Add KPI to the summary
        }

        // Add Culture and Leadership
        foreach (['Culture', 'Leadership'] as $formName) {
            if (isset($summedScores[$formName])) {
                $form = [
                    "formName" => $formName
                ];
                foreach ($summedScores[$formName] as $key => $scores) {
                    $form[$key] = $scores; // Include Culture or Leadership data in the summary
                }
                $summary['formData'][] = $form; // Add Culture or Leadership to the summary
            }
        }
        
        // Return both calculated data and summary
        return [
            'calculated_data' => $calculatedFormData,
            'summary' => $summary
        ];
    }
    
}