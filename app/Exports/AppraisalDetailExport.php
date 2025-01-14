<?php

namespace App\Exports;

use App\Models\AppraisalContributor;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Facades\Excel;

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

        foreach ($this->data as $row) {
            $employeeId = $row['Employee ID']['dataId'] ?? null;
            if ($employeeId && $contributorsGroupedByEmployee->has($employeeId)) {
                $this->expandRowForContributors($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));
            } else {
                $expandedData->push($this->createDefaultContributorRow($row));
            }
        }

        return $expandedData;
    }

    private function expandRowForContributors(Collection $expandedData, array $row, Collection $contributors): void
    {
        $summaryRow = $row; // Base row for summary
        $summaryRow['Contributor Type'] = ['dataId' => 'summary'];
        $summaryRow['Contributor ID'] = ['dataId' => '-'];

        // Initialize summary scores to zero
        $summaryRow['KPI Score']['dataId'] = 0;
        $summaryRow['Culture Score']['dataId'] = 0;
        $summaryRow['Leadership Score']['dataId'] = 0;
        $summaryRow['Total Score']['dataId'] = 0;

        $hasValidData = false; // Flag to check if there's any non-"-" data
        $contributorCount = 0; // Counter for valid contributors

        foreach ($contributors as $contributor) {
            $contributorRow = $row; // Clone the base row for each contributor
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

                // Accumulate scores for summary
                $summaryRow['KPI Score']['dataId'] += $formData['totalKpiScore'] ?? 0;
                $summaryRow['Culture Score']['dataId'] += $formData['totalCultureScore'] ?? 0;
                $summaryRow['Leadership Score']['dataId'] += $formData['totalLeadershipScore'] ?? 0;
                $summaryRow['Total Score']['dataId'] += $formData['totalScore'] ?? 0;
            }
        }

        // Add summary row only if there is valid data
        if ($hasValidData && $contributorCount > 0) {
            // Just round the sums without dividing by contributor count
            $summaryRow['KPI Score']['dataId'] = round($summaryRow['KPI Score']['dataId'], 2);
            $summaryRow['Culture Score']['dataId'] = round($summaryRow['Culture Score']['dataId'], 2);
            $summaryRow['Leadership Score']['dataId'] = round($summaryRow['Leadership Score']['dataId'], 2);
            $summaryRow['Total Score']['dataId'] = round($summaryRow['Total Score']['dataId'], 2);

            $expandedData->push($summaryRow);
        }
    }
    private function addFormDataToRow(array &$contributorRow, array $formData): void
    {
        if (isset($formData['formData'])) {
            foreach ($formData['formData'] as $formGroup) {
                $formName = $formGroup['formName'] ?? 'Unknown';
                foreach ($formGroup as $index => $itemGroup) {
                    if (is_array($itemGroup)) {
                        $this->processFormGroup($formName, $itemGroup, $contributorRow);
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
        if ($formName === 'Culture' || $formName === 'Leadership') {
            $this->processCultureOrLeadership($formName, $itemGroup, $contributorRow);
        } elseif ($formName === 'KPI') {
            $this->processKPI($formName, $itemGroup, $contributorRow);
        }
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

    private function processKPI(string $formName, array $itemGroup, array &$contributorRow): void
    {
        $key = 1; // In your code, you may need to dynamically calculate the key
        foreach ($itemGroup as $subKey => $value) {
            $kpiKey = strtolower(trim("{$formName}_{$subKey}_{$key}"));
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
        // Prepare the goal and appraisal data
        $goalData = json_decode($contributor->goal->form_data ?? '[]', true);
        $appraisalData = json_decode($contributor->form_data ?? '[]', true);
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

        $formData = $this->appService->combineFormData(
            $appraisalData,
            $goalData,
            $contributor->contributor_type,
            $employeeData,
            $contributor->period
        );

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
