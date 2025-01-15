<?php

namespace App\Exports;

use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalSnapshots;
use App\Models\MasterWeightage;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

class AppraisalDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    protected Collection $data;
    protected array $headers;
    protected AppService $appService;
    protected array $dynamicHeaders = [];

    public function __construct(AppService $appService, array $data, array $headers)
    {
        $this->data = collect($data); // Convert array data to a collection
        $this->headers = $headers;
        $this->appService = $appService;
    }

    public function collection(): Collection
    {
        $this->dynamicHeaders = []; // Reset dynamic headers for each export

        // Existing code for data collection
        $year = $this->appService->appraisalPeriod();

        $contributorsGroupedByEmployee = AppraisalContributor::with('employee')
            ->where('period', $year)
            ->get()
            ->groupBy('employee_id');

        $expandedData = collect();

        $this->data->chunk(100)->each(function ($rows) use ($expandedData, $contributorsGroupedByEmployee) {
            foreach ($this->data as $row) {
                $employeeId = $row['Employee ID']['dataId'] ?? null;
                $formId = $row['Form ID']['dataId'] ?? null;

                if ($employeeId && $contributorsGroupedByEmployee->has($employeeId)) {
                    $this->expandRowForSelf($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));
                    $this->expandRowForContributors($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));
                    $this->expandRowForSummary($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));

                } else {
                    $expandedData->push($this->createDefaultContributorRow($row));
                }
            }
        });

        return $expandedData;
    }

    private function expandRowForSummary(Collection $expandedData, array $row, Collection $contributors): void
    {
        $contributor = $contributors->first();

        if ($contributor) {
            $contributorRow = $row;
            $formData = $this->getFormDataSummary($contributor);
            $contributorRow['Contributor ID'] = ['dataId' => $contributor->employee_id];
            $contributorRow['Contributor Type'] = ['dataId' => 'summary'];
            $this->addFormDataToRow($contributorRow, $formData);

            // Add the processed row to expandedData
            $expandedData->push($contributorRow);
        }
    }

    private function expandRowForSelf(Collection $expandedData, array $row, Collection $contributors): void
    {
        $contributor = $contributors->first();

        if ($contributor) {
            $contributorRow = $row;
            $formData = $this->getFormDataSelf($contributor);
            $contributorRow['Contributor ID'] = ['dataId' => $contributor->employee_id];
            $contributorRow['Contributor Type'] = ['dataId' => 'self'];
            $this->addFormDataToRow($contributorRow, $formData);

            // Add the processed row to expandedData
            $expandedData->push($contributorRow);
        }
    }

    private function expandRowForContributors(Collection $expandedData, array $row, Collection $contributors): void
    {
        $summaryRow = $row; // Base row for summary
        $summaryRow['Contributor Type'] = ['dataId' => 'summary'];
        $summaryRow['Contributor ID'] = ['dataId' => '-'];

        // Initialize summary scores
        $summaryRow['KPI Score']['dataId'] = 0;
        $summaryRow['Culture Score']['dataId'] = 0;
        $summaryRow['Leadership Score']['dataId'] = 0;
        $summaryRow['Total Score']['dataId'] = 0;

        $hasValidData = false;
        $contributorCount = 0;
        $validCultureScores = [];
        $validLeadershipScores = [];

        foreach ($contributors as $contributor) {
            $contributorRow = $row;
            $formData = $this->getFormDataForContributor($contributor);

            // Check if the contributor has any valid scores
            $hasValidScores =
                ($formData['totalKpiScore'] ?? 0) != 0 ||
                ($formData['totalCultureScore'] ?? 0) != 0 ||
                ($formData['totalLeadershipScore'] ?? 0) != 0 ||
                ($formData['totalScore'] ?? 0) != 0;

            if ($hasValidScores) {
                $hasValidData = true;
                $contributorCount++;

                $contributorRow['Contributor ID'] = ['dataId' => $contributor->contributor_id];
                $contributorRow['Contributor Type'] = ['dataId' => $contributor->contributor_type];
                $this->addFormDataToRow($contributorRow, $formData);

                // Add individual contributor row to expandedData
                $expandedData->push($contributorRow);

                // Only take KPI Score from manager
                if ($contributor->contributor_type === 'manager') {
                    $summaryRow['KPI Score']['dataId'] = $formData['totalKpiScore'] ?? 0;
                }

                // Collect valid Culture and Leadership scores for averaging
                if (isset($formData['totalCultureScore']) && $formData['totalCultureScore'] != 0) {
                    $validCultureScores[] = $formData['totalCultureScore'];
                }
                if (isset($formData['totalLeadershipScore']) && $formData['totalLeadershipScore'] != 0) {
                    $validLeadershipScores[] = $formData['totalLeadershipScore'];
                }
            }
        }

        // Add summary row only if there is valid data
        if ($hasValidData && $contributorCount > 0) {
            // Calculate average for Culture Score if there are valid scores
            if (!empty($validCultureScores)) {
                $summaryRow['Culture Score']['dataId'] = round(array_sum($validCultureScores) / count($validCultureScores), 2);
            }

            // Calculate average for Leadership Score if there are valid scores
            if (!empty($validLeadershipScores)) {
                $summaryRow['Leadership Score']['dataId'] = round(array_sum($validLeadershipScores) / count($validLeadershipScores), 2);
            }

            // Calculate total score as sum of individual scores in summary row
            $summaryRow['Total Score']['dataId'] = round(
                $summaryRow['KPI Score']['dataId'] +
                $summaryRow['Culture Score']['dataId'] +
                $summaryRow['Leadership Score']['dataId'],
                2
            );

            $expandedData->push($summaryRow);
        }
    }
    private function addFormDataToRow(array &$contributorRow, array $formData): void
    {
        if (isset($formData['formData'])) {
            foreach ($formData['formData'] as $formGroup) {
                $formName = $formGroup['formName'] ?? 'Unknown';
                foreach ($formGroup as $index => $itemGroup) {
                    // Log::info('Preprocessing data to temp table', [
                    //     'data_preview' => $index, // Log only the first 10 rows
                    // ]);
                    if (is_array($itemGroup)) {
                        if ($formName === 'Culture' || $formName === 'Leadership') {
                            $this->processFormGroup($formName, $itemGroup, $contributorRow);
                        } elseif ($formName === 'KPI') {
                            $this->processKPI($formName, $itemGroup, $contributorRow, $index);
                        }
                    }
                }
            }

            $contributorRow['KPI Score'] = ['dataId' => round($formData['totalKpiScore'], 2) ?? '-'];
            $contributorRow['Culture Score'] = ['dataId' => round($formData['totalCultureScore'], 2) ?? '-'];
            $contributorRow['Leadership Score'] = ['dataId' => round($formData['totalLeadershipScore'], 2) ?? '-'];
            $contributorRow['Total Score'] = ['dataId' => round($formData['totalScore'], 2) ?? '-'];
        }
    }

    /**
     * Process the individual form group items and populate headers.
     */
    private function processFormGroup(string $formName, array $itemGroup, array &$contributorRow): void
    {
            $this->processCultureOrLeadership($formName, $itemGroup, $contributorRow);
    }

    private function processCultureOrLeadership(string $formName, array $itemGroup, array &$contributorRow): void
    {
        $title = $itemGroup['title'] ?? 'Unknown Title';

        foreach ($itemGroup as $subIndex => $item) {
            if (is_array($item) && isset($item['formItem'], $item['score'])) {
                $subNumber = $subIndex + 1;
                $header = strtolower(trim("{$formName}_{$title}_{$subNumber}"));
                $this->captureDynamicHeader($header);
                $contributorRow[$header] = ['dataId' => strip_tags($item['formItem']) . "|" . $item['score']];
            }
        }
    }

    private function processKPI(string $formName, array $itemGroup, array &$contributorRow, int $index): void
    {

        $itemGroup = [
            "kpi" => $itemGroup["kpi"],
            "target" => $itemGroup["target"],
            "achievement" => $itemGroup["achievement"],
            "uom" => $itemGroup["uom"],
            "weightage" => $itemGroup["weightage"],
            "type" => $itemGroup["type"],
            "custom_uom" => $itemGroup["custom_uom"],
            "percentage" => $itemGroup["percentage"],
            "conversion" => $itemGroup["conversion"],
            "final_score" => $itemGroup["final_score"],
        ];

        foreach ($itemGroup as $subKey => $value) {
            $subNumber = $index + 1;
            $kpiKey = strtolower(trim("{$formName}_{$subKey}_{$subNumber}"));
            $this->captureDynamicHeader($kpiKey);
            $contributorRow[$kpiKey] = ['dataId' => $value];
        }
    }

    // Helper function to capture unique dynamic headers
    private function captureDynamicHeader(string $header): void
    {
        if (!isset($this->dynamicHeaders[$header])) {
            $this->dynamicHeaders[$header] = $header;
        }
    }

    private function getFormDataForContributor(AppraisalContributor $contributor): array
    {
        $appraisal = Appraisal::with(['goal'])->where('id', $contributor->appraisal_id)->first();

        // Prepare the goal and appraisal data
        $goalData = json_decode($appraisal->goal->form_data ?? '[]', true);

        $appraisalData = json_decode($contributor->form_data ?? '[]', true);
        $appraisalData['contributor_type'] = $contributor->contributor_type;
        $appraisalData = array($appraisalData);

        $employeeData = $contributor->employee;

        $formGroupContent = $this->appService->formGroupAppraisal($contributor->employee_id, 'Appraisal Form');
        $appraisalForm = $formGroupContent ?: ['data' => ['formData' => []]];

        if (!$formGroupContent) {
            $appraisalForm = ['data' => ['formData' => []]];
        } else {
            $appraisalForm = $formGroupContent;
        }

        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

        $jobLevel = $employeeData->job_level;

        $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $contributor->period)->first();

        $weightageContent = json_decode($weightageData->form_data, true);

        $result = $this->appService->appraisalSummary($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);

        $formData = $this->appService->combineFormData($result['calculated_data'][0], $goalData, $contributor->contributor_type, $employeeData, $contributor->period);

        foreach ($formData['formData'] as &$form) {
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
        }

        return $formData;
    }

    private function getFormDataSelf(AppraisalContributor $contributor): array
    {
        $datas = Appraisal::with([
            'employee',
            'approvalSnapshots' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->where('id', $contributor->appraisal_id)->get();

        $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->goal->form_data, true) : [];
        $appraisalData = $datas->isNotEmpty() ? json_decode($datas->first()->approvalSnapshots->form_data, true) : [];

        $appraisalData['contributor_type'] = "employee";

        $appraisalData = array($appraisalData);

        // if ($datas->first()->employee) {
        //     Log::error('The $datas collection is empty. Cannot access employee property.', [
        //         'appraisal_id' => $contributor->appraisal_id,
        //     ]);
        //     throw new \Exception('No data found for the given appraisal ID.');
        // }

        $employeeData = $datas->first()->employee;

        // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor

        $formGroupContent = $this->appService->formGroupAppraisal($datas->first()->employee_id, 'Appraisal Form');

        if (!$formGroupContent) {
            $appraisalForm = ['data' => ['formData' => []]];
        } else {
            $appraisalForm = $formGroupContent;
        }

        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

        $jobLevel = $employeeData->job_level;

        $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $contributor->period)->first();

        $weightageContent = json_decode($weightageData->form_data, true);

        $result = $this->appService->appraisalSummary($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);

        $formData = $this->appService->combineFormData($result['calculated_data'][0], $goalData, 'employee', $employeeData, $datas->first()->period);

        foreach ($formData['formData'] as &$form) {
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
        }

        return $formData;
    }

    private function getFormDataSummary(AppraisalContributor $contributor): array
    {
        $datasQuery = AppraisalContributor::with(['employee'])->where('appraisal_id', $contributor->appraisal_id);
        $datas = $datasQuery->get();

        $checkSnapshot = ApprovalSnapshots::where('form_id', $contributor->appraisal_id)->where('created_by', $datas->first()->employee->id)
            ->orderBy('created_at', 'desc');

        // Check if `datas->first()->employee->id` exists
        if ($checkSnapshot) {
            $query = $checkSnapshot;
        }else{
            $query = ApprovalSnapshots::where('form_id', $contributor->appraisal_id)
            ->orderBy('created_at', 'asc');
        }

        $employeeForm = $query->first();

        $data = [];
        $appraisalDataCollection = [];
        $goalDataCollection = [];

        $formGroupContent = $this->appService->formGroupAppraisal($datas->first()->employee_id, 'Appraisal Form');

        if (!$formGroupContent) {
            $appraisalForm = ['data' => ['formData' => []]];
        } else {
            $appraisalForm = $formGroupContent;
        }

        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];


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
            $dataItem->goal = $request->goal;
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
            if ($request->goal && $request->goal->form_data) {
                $goalData = json_decode($request->goal->form_data, true);
                $goalDataCollection[] = $goalData;
            }

            // Combine the appraisal and goal data for each contributor
            $employeeData = $request->employee; // Get employee data

            $formData[] = $appraisalData;

        }

        $jobLevel = $employeeData->job_level;

        $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $request->period)->first();

        $weightageContent = json_decode($weightageData->form_data, true);

        $result = $this->appService->appraisalSummary($weightageContent, $formData, $employeeData->employee_id, $jobLevel);

        // $formData = $this->appService->combineFormData($result['summary'], $goalData, $result['summary']['contributor_type'], $employeeData, $request->period);

        $formData = $this->appService->combineSummaryFormData($result, $goalData, $employeeData, $request->period);

        foreach ($formData['formData'] as &$form) {
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
        }

        return $formData;
    }

    private function createDefaultContributorRow(array $row): array
    {
        $row['Contributor ID'] = ['dataId' => '-'];
        $row['Contributor Type'] = ['dataId' => '-'];
        $row['KPI Score'] = ['dataId' => '-'];
        $row['Culture Score'] = ['dataId' => '-'];
        $row['Leadership Score'] = ['dataId' => '-'];
        $row['Total Score'] = ['dataId' => '-'];
        return $row;
    }

    public function headings(): array
    {
        if (empty($this->dynamicHeaders)) {
            // Populate collection to ensure dynamic headers are captured
            $this->collection();
        }

        $extendedHeaders = $this->headers;

        foreach (['Contributor ID', 'Contributor Type', 'KPI Score', 'Culture Score', 'Leadership Score', 'Total Score'] as $header) {
            if (!in_array($header, $extendedHeaders)) {
                $extendedHeaders[] = $header;
            }
        }

        // Separate headers by category
        $kpiHeaders = [];
        $cultureHeaders = [];
        $leadershipHeaders = [];

        foreach ($this->dynamicHeaders as $header) {
            if (strpos($header, 'kpi_') === 0) {
                $kpiHeaders[] = $header;
            } elseif (strpos($header, 'culture_') === 0) {
                $cultureHeaders[] = $header;
            } elseif (strpos($header, 'leadership_') === 0) {
                $leadershipHeaders[] = $header;
            }
        }

        // Sort KPI headers by numeric index
        usort($kpiHeaders, function ($a, $b) {
            // Extract the numeric part after 'kpi_' and before the next '_'
            preg_match('/kpi_(\d+)_/', $a, $aMatches);
            preg_match('/kpi_(\d+)_/', $b, $bMatches);
            $aIndex = isset($aMatches[1]) ? (int) $aMatches[1] : 0;
            $bIndex = isset($bMatches[1]) ? (int) $bMatches[1] : 0;

            return $aIndex <=> $bIndex;
        });

        // Sort Culture and Leadership headers alphabetically
        sort($cultureHeaders);
        sort($leadershipHeaders);

        // Merge all sorted headers back in the desired order
        $sortedDynamicHeaders = array_merge($kpiHeaders, $cultureHeaders, $leadershipHeaders);

        // Add sorted dynamic headers to the extended headers
        foreach ($sortedDynamicHeaders as $header) {
            if (!in_array($header, $extendedHeaders)) {
                $extendedHeaders[] = $header;
            }
        }

        // Log::info("Headings returned:", $extendedHeaders);
        return $extendedHeaders;
    }


    public function map($row): array
    {
        $data = [];
        foreach ($this->headings() as $header) {
            $data[] = $row[$header]['dataId'] ?? '';
        }
        return $data;
    }

    public function chunkSize(): int
    {
        return 1000; // Adjust based on your data size
    }
}
