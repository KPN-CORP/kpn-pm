<?php

namespace App\Exports;

use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\MasterWeightage;
use App\Services\AppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Generator;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

class AppraisalDetailExport implements FromGenerator, WithHeadings, WithMapping, WithChunkReading
{
    protected Collection $data;
    protected array $headers;
    protected AppService $appService;
    protected array $dynamicHeaders = [];
    protected $user;
    protected $period;

    // Caches to avoid re-running the (expensive) expansion and header build.
    protected ?Collection $expandedCache = null;
    protected ?array $headingsCache = null;

    // Memoized lookups that are identical for many contributors/rows.
    protected array $formGroupCache = [];   // keyed by employee_id
    protected array $weightageCache = [];   // keyed by "group_company|period"
    protected ?bool $isSuperadmin = null;

    // Preloaded Appraisal models (with goal + employee) keyed by Appraisal id.
    protected $appraisalById = null;

    // Preload untuk baris summary: semua contributor per appraisal_id (dengan
    // employee + appraisal.goal) dan snapshot per form_id. Dulu keduanya
    // di-query ulang untuk setiap baris summary, ditambah lazy load goal per
    // contributor.
    protected ?Collection $contributorsByAppraisal = null;
    protected ?Collection $snapshotsByForm = null;

    /** @var callable|null fn(string $phase, int $done, int $total) — phase: 'expand' | 'write' */
    protected $onProgress;

    public function __construct(AppService $appService, array $data, array $headers, $user, $period, ?callable $onProgress = null)
    {
        $this->data = collect($data); // Convert array data to a collection
        $this->headers = $headers;
        $this->appService = $appService;
        $this->user = $user;
        $this->period = $period;
        $this->isSuperadmin = $user ? $user->hasRole('superadmin') : false;
        $this->onProgress = $onProgress;
    }

    public function collection(): Collection
    {
        // Expansion is expensive (per-contributor DB work + calculations) and is
        // triggered from both headings() and the sheet writer. Build it once.
        if ($this->expandedCache !== null) {
            return $this->expandedCache;
        }

        $this->dynamicHeaders = []; // Reset dynamic headers for each export

        // Only this batch's rows are ever looked up below, so scope the lookup
        // maps to the batch instead of loading the whole period on every batch.
        $employeeIds = $this->data
            ->map(fn($row) => $row['Employee ID']['dataId'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $formIds = $this->data
            ->map(fn($row) => $row['Form ID']['dataId'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $contributorsGroupedByEmployee = AppraisalContributor::with([
            'employee' => function ($query) {
                $query->select('employee_id', 'fullname', 'gender', 'email', 'job_level', 'group_company', 'designation_name', 'company_name', 'contribution_level_code'); // Adjust fields as needed
            }
        ])
        ->where('period', $this->period)
        ->whereIn('employee_id', $employeeIds)
        ->get()
        ->groupBy('employee_id');

        // Baris "self" dulu meng-query ulang appraisal ini (dengan employee
        // lengkap + snapshot) per baris. Sekarang ikut di-preload di sini,
        // dengan constraint snapshot yang sama persis seperti query lamanya.
        $employeeAppraisalById = Appraisal::with([
            'employee',
            'approvalSnapshots' => function ($query) {
                $query->latest()
                    ->where('form_data->formGroupName', '!=', 'Appraisal Form 360');
            },
            'goal', // preloaded so per-contributor/summary lookups don't re-query
        ])
        ->where('period', $this->period)
        ->whereIn('id', $formIds)
        ->get()
        ->groupBy('id');

        // Keyed map (Appraisal id => model) reused by contributor/summary methods.
        $this->appraisalById = $employeeAppraisalById;

        $this->preloadSummaryData($contributorsGroupedByEmployee);

        $expandedData = collect();

        $totalRows = $this->data->count();
        $processedRows = 0;

        $this->data->chunk(100)->each(function ($chunk) use ($expandedData, $contributorsGroupedByEmployee, $employeeAppraisalById, &$processedRows, $totalRows) {
            foreach ($chunk as $row) {
                $employeeId = $row['Employee ID']['dataId'] ?? null;
                $formId = $row['Form ID']['dataId'] ?? null;

                
                if ($formId && $employeeId) {
                    // Log::info('Preprocessing data export', [
                    //     'data_preview' => $employeeAppraisalById->get($formId),
                    // ]);
                    $this->expandRowForSelf($expandedData, $row, $employeeAppraisalById->get($formId));

                    if ($contributorsGroupedByEmployee->has($employeeId)) {
                        $this->expandRowForContributors($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));
                        $this->expandRowForSummary($expandedData, $row, $contributorsGroupedByEmployee->get($employeeId));
    
                    }
                } else {
                    $expandedData->push($this->compactRow($this->createDefaultContributorRow($row)));
                }

            }

            $processedRows += $chunk->count();

            if ($this->onProgress) {
                ($this->onProgress)('expand', $processedRows, $totalRows);
            }
        });

        $this->expandedCache = $expandedData;

        // Preload hanya dibutuhkan selama ekspansi; lepaskan supaya memorinya
        // bisa dipakai PhpSpreadsheet saat menulis file.
        $this->appraisalById = null;
        $this->contributorsByAppraisal = null;
        $this->snapshotsByForm = null;

        return $expandedData;
    }

    /**
     * Baris dikirim satu per satu ke writer. Dengan FromCollection, Laravel
     * Excel membuat salinan penuh semua baris hasil map() sebelum menulis;
     * di sini tiap baris dilepas dari memori begitu sudah diserahkan.
     */
    public function generator(): Generator
    {
        $rows = $this->collection()->all();

        // Kosongkan cache supaya $rows jadi satu-satunya pemilik array-nya
        // (unset di bawah tidak memicu salinan). headings() sudah dipanggil
        // sebelum generator, dan hasilnya di-cache.
        $this->expandedCache = collect();

        $total = count($rows);

        for ($i = 0; $i < $total; $i++) {
            $row = $rows[$i];
            unset($rows[$i]);

            yield $row;

            if ($this->onProgress && (($i + 1) % 500 === 0 || $i + 1 === $total)) {
                ($this->onProgress)('write', $i + 1, $total);
            }
        }
    }

    /**
     * Simpan baris dalam bentuk datar (header => nilai) alih-alih
     * header => ['dataId' => nilai]: satu array kecil per sel memakan
     * memori paling besar untuk report dengan ~150 kolom.
     */
    private function compactRow(array $row): array
    {
        foreach ($row as $header => $cell) {
            $row[$header] = is_array($cell) ? ($cell['dataId'] ?? null) : null;
        }

        return $row;
    }

    /**
     * Baris summary memakai contributor pertama tiap karyawan; preload semua
     * contributor untuk appraisal_id tersebut beserta snapshot form-nya.
     */
    private function preloadSummaryData(Collection $contributorsGroupedByEmployee): void
    {
        $appraisalIds = $contributorsGroupedByEmployee
            ->map(fn ($contributors) => optional($contributors->first())->appraisal_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($appraisalIds)) {
            $this->contributorsByAppraisal = collect();
            $this->snapshotsByForm = collect();

            return;
        }

        // orderBy('id') = urutan yang sama dengan query lama per appraisal_id.
        $this->contributorsByAppraisal = AppraisalContributor::with(['employee', 'appraisal.goal'])
            ->whereIn('appraisal_id', $appraisalIds)
            ->orderBy('id')
            ->get()
            ->groupBy('appraisal_id');

        $this->snapshotsByForm = ApprovalSnapshots::with('employee')
            ->whereIn('form_id', $appraisalIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('form_id');
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
            $expandedData->push($this->compactRow($contributorRow));
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
            $expandedData->push($this->compactRow($contributorRow));
        }
    }

    private function expandRowForContributors(Collection $expandedData, array $row, Collection $contributors): void
    {
        foreach ($contributors as $contributor) {
            $contributorRow = $row;
            $formData = $this->getFormDataForContributor($contributor);
            $contributorRow['Contributor ID'] = ['dataId' => $contributor->contributor_id];
            $contributorRow['Contributor Type'] = ['dataId' => $contributor->contributor_type];
            $this->addFormDataToRow($contributorRow, $formData);
            $expandedData->push($this->compactRow($contributorRow));
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
        $maxKpi = 10;
        $index = min($index, $maxKpi - 1); // Ensure index stays within 0-9

        // Normalize / safeguard keys to avoid "Undefined array key" errors
        $itemGroupSafe = [
            'kpi' => $itemGroup['kpi'] ?? null,
            'target' => $itemGroup['target'] ?? null,
            'achievement' => $itemGroup['achievement'] ?? null,
            'uom' => $itemGroup['uom'] ?? null,
            'weightage' => $itemGroup['weightage'] ?? null,
            'type' => $itemGroup['type'] ?? null,
            'custom_uom' => $itemGroup['custom_uom'] ?? null,
            'percentage' => $itemGroup['percentage'] ?? null,
            'conversion' => $itemGroup['conversion'] ?? null,
            'final_score' => $itemGroup['final_score'] ?? null,
        ];

        // Generate headers for ALL 10 KPI positions (1-10)
        for ($kpiNumber = 1; $kpiNumber <= $maxKpi; $kpiNumber++) {
            foreach ($itemGroupSafe as $subKey => $value) {
                $kpiKey = strtolower(trim("{$formName}_{$subKey}_{$kpiNumber}"));
                $this->captureDynamicHeader($kpiKey);
            }
        }

        // Process current KPI's data (use safe values and default to empty string)
        foreach ($itemGroupSafe as $subKey => $value) {
            $subNumber = $index + 1; // Convert to 1-based index
            $kpiKey = strtolower(trim("{$formName}_{$subKey}_{$subNumber}"));
            $contributorRow[$kpiKey] = ['dataId' => $value ?? ''];
        }
    }

    // Helper function to capture unique dynamic headers
    private function captureDynamicHeader(string $header): void
    {
        if (!isset($this->dynamicHeaders[$header])) {
            $this->dynamicHeaders[$header] = $header;
        }
    }

    /**
     * The "Appraisal Form" template resolves identically for a given employee,
     * yet is requested for self, every 360 contributor and the summary row.
     * Memoize it per employee to collapse those repeated 2-query lookups.
     */
    private function resolveFormGroup($employeeId): array
    {
        if (!array_key_exists($employeeId, $this->formGroupCache)) {
            $content = $this->appService->formGroupAppraisal($employeeId, 'Appraisal Form');
            $this->formGroupCache[$employeeId] = $content ?: ['data' => ['formData' => []]];
        }

        return $this->formGroupCache[$employeeId];
    }

    /**
     * Weightage is the same for everyone in a group_company/period, so cache
     * the decoded content instead of running the LIKE query per contributor.
     */
    private function resolveWeightageContent($groupCompany, $period)
    {
        $key = $groupCompany . '|' . $period;

        if (!array_key_exists($key, $this->weightageCache)) {
            $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $groupCompany . '%')
                ->where('period', $period)
                ->first();
            $this->weightageCache[$key] = $weightageData ? json_decode($weightageData->form_data, true) : null;
        }

        return $this->weightageCache[$key];
    }

    /**
     * Look up a preloaded Appraisal (with goal + employee eager loaded) by id,
     * falling back to a direct query if it was not part of the batch's period set.
     */
    private function resolveAppraisal($appraisalId)
    {
        $appraisal = optional($this->appraisalById?->get($appraisalId))->first();

        if (!$appraisal) {
            $appraisal = Appraisal::with(['goal'])->where('id', $appraisalId)->first();
        }

        return $appraisal;
    }

    private function getFormDataForContributor(AppraisalContributor $contributor): array
    {
        $appraisal = $this->resolveAppraisal($contributor->appraisal_id);

        // If the underlying appraisal is still a Draft, skip heavy calculations
        if ($appraisal && (($appraisal->form_status ?? null) === 'Draft' || ($contributor->status ?? null) === 'Draft')) {
            return [
                'formData' => [],
                'totalKpiScore' => null,
                'totalCultureScore' => null,
                'totalLeadershipScore' => null,
                'totalScore' => null,
            ];
        }

        // Prepare the goal and appraisal data
        $goalData = is_array($appraisal->goal->form_data ?? null)
            ? $appraisal->goal->form_data
            : json_decode($appraisal->goal->form_data ?? '[]', true) ?? [];

        $appraisalData = json_decode($contributor->form_data ?? '[]', true);
        $appraisalData['contributor_type'] = $contributor->contributor_type;
        $appraisalData = array($appraisalData);

        $employeeData = $contributor->employee;

        $appraisalForm = $this->resolveFormGroup($contributor->employee_id);

        // culture & leadership BI items
        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

        $jobLevel = $employeeData->job_level;

        $weightageContent = $this->resolveWeightageContent($employeeData->group_company, $contributor->period);

        if ($this->isSuperadmin) {
            // for non percentage by 360 data BI items
            $result = $this->appService->appraisalSummaryWithout360Calculation($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);
        } else {
            $result = $this->appService->appraisalSummary($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);
        }

        $formData = $this->appService->combineFormData($result['calculated_data'][0], $goalData, $contributor->contributor_type, $employeeData, $contributor->period);

        // Log::info('Debug Export Data', [
        //     'data' => $this->user, // Log only the first 10 rows
        // ]);

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

    private function getFormDataSelf(Appraisal $contributor): array
    {
        // $contributor adalah Appraisal hasil preload di collection() (employee,
        // approvalSnapshots, goal sudah ter-load) — tidak perlu query ulang.
        $datas = collect([$contributor]);

        // If appraisal is Draft, return empty structure to avoid calculations
        if ($datas->isNotEmpty() && (($datas->first()->form_status ?? null) === 'Draft')) {
            return [
                'formData' => [],
                'totalKpiScore' => null,
                'totalCultureScore' => null,
                'totalLeadershipScore' => null,
                'totalScore' => null,
            ];
        }

        if(!$datas->first()->approvalSnapshots){
            Log::info('Debug Snapshots Data', [
                'data' => $datas->first()->employee_id, // Log only the first 10 rows
            ]);
        }

        $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->goal->form_data, true) : [];
        $appraisalData = $datas->isNotEmpty() ? json_decode($datas->first()->approvalSnapshots->form_data, true) : [];

        $appraisalData['contributor_type'] = "employee";

        $appraisalData = array($appraisalData);

        $data = $datas->first();
        $employeeData = $data ? $data->employee : null;

        // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor

        $appraisalForm = $this->resolveFormGroup($datas->first()->employee_id);

        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

        $jobLevel = $employeeData->job_level;

        $weightageContent = $this->resolveWeightageContent($employeeData->group_company, $contributor->period);

        if ($this->isSuperadmin) {
            // for non percentage by 360 data BI items
            $result = $this->appService->appraisalSummaryWithout360Calculation($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);
        } else {
            $result = $this->appService->appraisalSummary($weightageContent, $appraisalData, $employeeData->employee_id, $jobLevel);
        }

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
        $datas = $this->contributorsByAppraisal?->get($contributor->appraisal_id)
            ?? AppraisalContributor::with(['employee', 'appraisal.goal'])->where('appraisal_id', $contributor->appraisal_id)->orderBy('id')->get();

        // If the appraisal itself is Draft, skip and return empty structure
        $appraisalModel = $this->resolveAppraisal($contributor->appraisal_id);
        if ($appraisalModel && (($appraisalModel->form_status ?? null) === 'Draft')) {
            return [
                'formData' => [],
                'totalKpiScore' => null,
                'totalCultureScore' => null,
                'totalLeadershipScore' => null,
                'totalScore' => null,
            ];
        }

        // Snapshot terbaru milik karyawan untuk form ini (dulu satu query per
        // baris; sekarang dari preload). Catatan: cabang fallback "asc" di
        // kode lama tidak pernah jalan karena query builder selalu truthy.
        $createdBy = $datas->first()->employee->id;

        if ($this->snapshotsByForm !== null) {
            $employeeForm = $this->snapshotsByForm->get($contributor->appraisal_id, collect())
                ->first(fn ($snapshot) => $snapshot->created_by == $createdBy);
        } else {
            $employeeForm = ApprovalSnapshots::where('form_id', $contributor->appraisal_id)->where('created_by', $createdBy)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $data = [];
        $appraisalDataCollection = [];
        $goalDataCollection = [];

        $appraisalForm = $this->resolveFormGroup($datas->first()->employee_id);

        $cultureData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
        $leadershipData = $this->appService->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];


        if ($employeeForm) {

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

        $weightageContent = $this->resolveWeightageContent($employeeData->group_company, $request->period);

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
                                'score' => round($form[$index][$itemIndex]['average'], 2)
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
                                'score' => round($form[$index][$itemIndex]['average'], 2)
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
        // Built once and reused; map() calls this per row, so recomputing the
        // sort/merge here every time was pure overhead.
        if ($this->headingsCache !== null) {
            return $this->headingsCache;
        }

        // Ensure the expansion has run so dynamic headers are populated (cached).
        $this->collection();

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
        $this->headingsCache = $extendedHeaders;

        return $extendedHeaders;
    }


    public function map($row): array
    {
        // $row sudah datar, lihat compactRow().
        $data = [];
        foreach ($this->headings() as $header) {
            $data[] = $row[$header] ?? '';
        }
        return $data;
    }

    public function chunkSize(): int
    {
        return 1000; // Adjust based on your data size
    }
}