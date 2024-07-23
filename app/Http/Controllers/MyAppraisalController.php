<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\Appraisal;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use stdClass;

class MyAppraisalController extends Controller
{

    protected $category;
    protected $user;

    public function __construct()
    {
        $this->user = Auth()->user()->employee_id;
        $this->category = 'Appraisals';
    }

    function formatDate($date)
    {
        // Parse the date using Carbon
        $carbonDate = Carbon::parse($date);

        // Check if the date is today
        if ($carbonDate->isToday()) {
            return 'Today ' . $carbonDate->format('ga');
        } else {
            return $carbonDate->format('d M ga');
        }
    }

    public function create(Request $request)
    {
        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $goal = Goal::where('employee_id', $request->id)->whereYear('created_at', $year)->first();

        $appraisal = Appraisal::where('employee_id', $request->id)->whereYear('created_at', $year)->first();

        // $existingGoal = Goal::where('employee_id', $request->employee_id)
        //     ->where('category', $request->category)
        //     ->whereYear('created_at', $year)
        //     ->first();

        // if ($existingGoal) {
        //     Session::flash('error', "You already initiated Goals for $year.");
        //     return redirect()->back();
        // }

        // check goals
        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Your Goals for $year are not found.");
            return redirect()->back();
        }

        // check appraisals
        if ($appraisal) {
            Session::flash('error', "You already initiated Appraisal for $year.");
            return redirect()->back();
        }

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/testFormGroup.json');

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
        
        
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];

        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });
        
        $parentLink = 'Appraisals';
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages/appraisals/create', compact('step', 'parentLink', 'link', 'filteredFormData', 'formGroupData', 'goalData', 'goal', 'approval'));
    }

    public function store(Request $request)
    {
        $submit_status = 'Submitted';
        $period = 2024;
        // Validate the request data
        $validatedData = $request->validate([
            'employee_id' => 'required|string|size:11',
            'approver_id' => 'required|string|size:11',
            'formGroupName' => 'required|string|min:5|max:100',
            'formData' => 'required|array',
        ]);

        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];

        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];

        // Create a new Appraisal instance and save the data
        $appraisal = new Appraisal;
        $appraisal->id = Str::uuid();
        $appraisal->employee_id = $validatedData['employee_id'];
        $appraisal->category = $this->category;
        $appraisal->form_data = json_encode($datas); // Store the form data as JSON
        $appraisal->form_status = $submit_status;
        $appraisal->period = $period;
        $appraisal->created_by = Auth::user()->id;
        
        $appraisal->save();
        
        $snapshot =  new ApprovalSnapshots;
        $snapshot->id = Str::uuid();
        $snapshot->form_id = $appraisal->id;
        $snapshot->form_data = json_encode($datas);
        $snapshot->employee_id = $validatedData['employee_id'];
        $snapshot->created_by = Auth::user()->id;
        
        $snapshot->save();

        $approval = new ApprovalRequest();
        $approval->form_id = $appraisal->id;
        $approval->category = $this->category;
        $approval->employee_id = $validatedData['employee_id'];
        $approval->current_approval_id = $validatedData['approver_id'];
        $approval->created_by = Auth::user()->id;
        // Set other attributes as needed
        $approval->save();

        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals')->with('success', 'Data submitted successfully.');
    }

    private function combineFormData($appraisalData, $goalData) {
        foreach ($appraisalData['formData'] as &$form) {
            if ($form['formName'] === "Self Review") {
                foreach ($form as $key => &$entry) {
                    if (is_array($entry) && isset($goalData[$key])) {
                        $entry = array_merge($entry, $goalData[$key]);

                        // Adding "percentage" key
                        if (isset($entry['achievement'], $entry['target'], $entry['type'])) {
                            $entry['percentage'] = $this->evaluate($entry['achievement'], $entry['target'], $entry['type']);
                            $entry['conversion'] = $this->conversion($entry['percentage']);
                            $entry['final_score'] = $entry['conversion']*$entry['weightage']/100;
                        }
                    }
                }
            }
        }
        return $appraisalData;
    }

    private function evaluate($achievement, $target, $type) {
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

    private function conversion($evaluate) {
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

    private function getDataByName($data, $name) {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    public function index(Request $request) {
        try {
            $user = Auth::user()->employee_id;
            $filterYear = $request->input('filterYear');

            // Retrieve approval requests
            $datasQuery = ApprovalRequest::with([
                'employee', 'appraisal', 'updatedBy', 'adjustedBy', 'initiated', 'manager', 
                'approval' => function ($query) {
                    $query->with('approverName');
                }
            ])
            ->whereHas('approvalLayerAppraisal', function ($query) use ($user) {
                $query->where('employee_id', $user)->orWhere('approver_id', $user);
            })
            ->where('employee_id', $user)->where('category', $this->category);

            if (!empty($filterYear)) {
                $datasQuery->whereYear('created_at', $filterYear);
            }

            $datas = $datasQuery->get();

            $formattedData = $datas->map(function($item) {
                $createdDate = Carbon::parse($item->created_at);
                $item->formatted_created_at = $createdDate->isToday() ? 'Today ' . $createdDate->format('g:i A') : $createdDate->format('d M Y');

                $updatedDate = Carbon::parse($item->updated_at);
                $item->formatted_updated_at = $updatedDate->isToday() ? 'Today ' . $updatedDate->format('g:i A') : $updatedDate->format('d M Y');

                if ($item->sendback_to == $item->employee->employee_id) {
                    $item->name = $item->employee->fullname . ' (' . $item->employee->employee_id . ')';
                    $item->approvalLayer = '';
                } else {
                    $item->name = $item->manager->fullname . ' (' . $item->manager->employee_id . ')';
                    $item->approvalLayer = ApprovalLayerAppraisal::where('employee_id', $item->employee_id)
                                                        ->where('approver_id', $item->current_approval_id)
                                                        ->value('layer');
                }
                return $item;
            });

            $adjustByManager = $datas->first()->updatedBy ? 
                ApprovalLayerAppraisal::where('approver_id', $datas->first()->updatedBy->employee_id)
                    ->where('employee_id', $datas->first()->employee_id)
                    ->first() : null;

            $data = [];
            foreach ($formattedData as $request) {
                if ($request->goal->form_status != 'Draft' || $request->created_by == Auth::user()->id) {
                    $dataApprover = $request->approval->first() ? $request->approval->first()->approverName->fullname : '';
                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->approver_name = $dataApprover;
                    $dataItem->name = $request->name;
                    $dataItem->approvalLayer = $request->approvalLayer;
                    $data[] = $dataItem;
                }
            }

            $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->goal->form_data, true) : [];
            $appraisalData = $datas->isNotEmpty() ? json_decode($datas->first()->appraisal->form_data, true) : [];

            $formData = $this->combineFormData($appraisalData, $goalData);

            $formGroupContent = storage_path('../resources/testFormGroup.json');
            if (!File::exists($formGroupContent)) {
                $appraisalForm = ['data' => ['formData' => []]];
            } else {
                $appraisalForm = json_decode(File::get($formGroupContent), true);
            }

            $cultureData = $this->getDataByName($appraisalForm['data']['formData'], 'Culture') ?? [];
            $leadershipData = $this->getDataByName($appraisalForm['data']['formData'], 'Leadership') ?? [];

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

            $path = storage_path('../resources/goal.json');
            if (!File::exists($path)) {
                $options = ['UoM' => [], 'Type' => []];
            } else {
                $options = json_decode(File::get($path), true);
            }

            $uomOption = $options['UoM'] ?? [];
            $typeOption = $options['Type'] ?? [];

            $parentLink = 'Appraisals';
            $link = 'My Appraisals';

            $employee = Employee::where('employee_id', $user)->first();
            if (!$employee) {
                $access_menu = ['goals' => null];
            } else {
                $access_menu = json_decode($employee->access_menu, true);
            }
            $goals = $access_menu['goals'] ?? null;

            $selectYear = ApprovalRequest::where('employee_id', $user)->select('created_at')->get();
            $selectYear->transform(function ($req) {
                $req->year = Carbon::parse($req->created_at)->format('Y');
                return $req;
            });

            return view('pages.appraisals.my-appraisal', compact('data', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption', 'goals', 'selectYear', 'adjustByManager', 'appraisalData'));
        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals.my-appraisal', [
                'data' => [],
                'link' => 'My Appraisals',
                'parentLink' => 'Appraisals',
                'formData' => ['formData' => []],
                'uomOption' => [],
                'typeOption' => [],
                'goals' => null,
                'selectYear' => [],
                'adjustByManager' => null,
                'appraisalData' => []
            ]);
        }
    }
    
    


    function show($id) {
        $data = Goal::find($id);
        
        return view('pages.goals.modal', compact('data')); //modal body hilang ketika modal show bentrok dengan view goal
    }
    

    function edit(Request $request) {

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $appraisal = Appraisal::with(['approvalRequest'])->where('id', $request->id)->first();

        $goal = Goal::where('employee_id', $appraisal->employee_id)->whereYear('created_at', $year)->first();

        $approvalRequest = ApprovalRequest::where('form_id', $appraisal->id)->first();

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/testFormGroup.json');

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
        
        
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];

        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $appraisal->employee_id)->where('layer', 1)->first();

        $year = Carbon::now()->year;

        $goal = Goal::where('employee_id', $appraisal->employee_id)->whereYear('created_at', $year)->first();

        $appraisal = Appraisal::where('employee_id', $appraisal->employee_id)->whereYear('created_at', $year)->first();

        // check goals
        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Your Goals for $year are not found.");
            return redirect()->back();
        }

        $parentLink = 'Appraisals';
        $link = 'Edit';

        if(!$appraisal){
            return redirect()->route('appraisals');
        }else{
            // Read the contents of the JSON file
            $formData = json_decode($appraisal->form_data, true);

            $formCount = count($formData);

            $data = json_decode($appraisal->form_data, true);

            // dd($data);

            foreach ($data['formData'] as &$form) {

                dd($form);
                foreach ($data as $index => $dataItem) {
                    foreach ($dataItem['formData'] as $itemIndex => $item) {
                        $form[$index][$itemIndex];
                    }
                }
            }

            // foreach ($cultureData as $index => $cultureItem) {
            //     foreach ($cultureItem['items'] as $itemIndex => $item) {
            //         if (isset($form[$index][$itemIndex])) {
            //             $form[$index][$itemIndex] = [
            //                 'formItem' => $item,
            //                 'score' => $form[$index][$itemIndex]['score']
            //             ];
            //         }
            //     }
            //     $form[$index]['title'] = $cultureItem['title'];
            // }

            dd($data);

            return view('pages.appraisals.edit', compact('step', 'goal', 'appraisal', 'goalData', 'formCount', 'filteredFormData', 'link', 'data', 'approvalRequest', 'parentLink', 'approval', 'formGroupData'));
        }

    }

    function update(Request $request) {

        if ($request->submit_type === 'save_draft') {
            // Tangani logika penyimpanan sebagai draft
            $submit_status = 'Draft';
        } else {
            $submit_status = 'Submitted';
        }
        // Inisialisasi array untuk menyimpan pesan validasi kustom
        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $status = $submit_status;
        $custom_uoms = $request->input('custom_uom', []);

        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|string',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|integer|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.integer' => 'Weightage harus berupa angka.',
            'weightage.*.min' => 'Weightage harus lebih besar atau sama dengan :min %.',
            'weightage.*.max' => 'Weightage harus kurang dari atau sama dengan :max %.',
        ];

        // Membuat Validator instance
        if ($request->submit_type === 'submit_form') {
            $validator = Validator::make($request->all(), $rules, $customMessages);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        $kpiData = [];
        // Reset nomor indeks untuk penggunaan berikutnya
        $index = 1;

        // Iterasi melalui input untuk mendapatkan data KPI
        foreach ($kpis as $index => $kpi) {
            // Memastikan ada nilai untuk semua input terkait
            if ($submit_status=='Draft' || isset($targets[$index], $uoms[$index], $weightages[$index], $types[$index])) {
                // Simpan data KPI ke dalam array dengan nomor indeks sebagai kunci
                if($custom_uoms[$index]){
                    $customuom = $custom_uoms[$index];
                }else{
                    $customuom = null;
                }

                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'target' => $targets[$index],
                    'uom' => $uoms[$index],
                    'weightage' => $weightages[$index],
                    'type' => $types[$index],
                    'custom_uom' => $customuom
                ];

                $index++;
            }
        }

        // Simpan data KPI ke dalam file JSON
        $jsonData = json_encode($kpiData);

        $goal = Goal::find($request->id);
        $goal->form_data = $jsonData;
        $goal->form_status = $status;
        
        $goal->save();

        $approval = ApprovalRequest::where('form_id', $request->id)->first();
        $approval->status = 'Pending';
        $approval->sendback_messages = null;
        $approval->sendback_to = null;
        // Set other attributes as needed
        $approval->save();

        $snapshot =  ApprovalSnapshots::where('form_id', $request->id)->where('employee_id', $request->employee_id)->first();
        $snapshot->form_data = $jsonData;
        $snapshot->updated_by = Auth::user()->id;
        
        $snapshot->save();

        return redirect('goals');
       
    }

}
