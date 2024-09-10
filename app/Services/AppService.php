<?php

namespace App\Services;

use App\Models\MasterWeightage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;

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
}