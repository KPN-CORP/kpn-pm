<?php

namespace App\Exports;

use App\Models\AppraisalContributor;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppraisalDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        foreach ($contributors as $contributor) {
            $contributorRow = $row;
            $formData = $this->getFormDataForContributor($contributor);
            $contributorRow['Contributor ID'] = ['dataId' => $contributor->contributor_id];
            $contributorRow['Contributor Type'] = ['dataId' => $contributor->contributor_type];
            $this->addFormDataToRow($contributorRow, $formData);
            $expandedData->push($contributorRow);
        }
    }

    private function addFormDataToRow(array &$contributorRow, array $formData): void
    {
        if (isset($formData['formData'])) {
            foreach ($formData['formData'] as $formGroup) {
                $formName = $formGroup['formName'] ?? 'Unknown';

                foreach ($formGroup as $index => $itemGroup) {
                    if (is_array($itemGroup)) {
                        if ($formName === 'Culture' || $formName === 'Leadership') {
                            $title = $itemGroup['title'] ?? 'Unknown Title';
                            foreach ($itemGroup as $subIndex => $item) {
                                if (is_array($item) && isset($item['formItem'], $item['score'])) {
                                    $subNumber = $subIndex + 1;
                                    $header = $formName.'_'.$title.'_'.$subNumber;
                                    if (!isset($this->dynamicHeaders[$header])) {
                                        $this->dynamicHeaders[$header] = $header;
                                    }
                                    $contributorRow[$header] = ['dataId' => strip_tags($item['formItem']) . "|" . $item['score']];
                                }
                            }
                        } elseif ($formName === 'KPI') {
                            $key = $index + 1;
                            foreach ($itemGroup as $subKey => $value) {
                                $kpiKey = $formName.'_'.$key.'_'.$subKey;
                                if (!isset($this->dynamicHeaders[$kpiKey])) {
                                    $this->dynamicHeaders[$kpiKey] = $kpiKey;
                                }
                                $contributorRow[$kpiKey] = ['dataId' => $kpiKey . "|" . $value];
                            }
                        }
                    }
                }
            }

            $contributorRow['KPI Score'] = ['dataId' => round($formData['kpiScore'], 2) ?? '-'];
            $contributorRow['Culture Score'] = ['dataId' => round($formData['cultureScore'], 2) ?? '-'];
            $contributorRow['Leadership Score'] = ['dataId' => round($formData['leadershipScore'], 2) ?? '-'];
            $contributorRow['Total Score'] = ['dataId' => round($formData['totalScore'], 2) ?? '-'];
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
        $extendedHeaders = $this->headers;

        foreach (['Contributor ID', 'Contributor Type', 'KPI Score', 'Culture Score', 'Leadership Score', 'Total Score'] as $header) {
            if (!in_array($header, $extendedHeaders)) {
                $extendedHeaders[] = $header;
            }
        }

        foreach ($this->dynamicHeaders as $header) {
            if (!in_array($header, $extendedHeaders)) {
                $extendedHeaders[] = $header;
            }
        }

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
}
