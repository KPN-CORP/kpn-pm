<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAppraisal;
use App\Models\FormAppraisal;
use App\Models\MasterCompetencyType;
use App\Models\MasterWeightage;
use App\Models\MasterWeightage360;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Empty_;

use function PHPUnit\Framework\isEmpty;

class WeightageController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth()->user();
    }

    public function index(Request $request) {
        try {

            // Retrieve master weightage
            $datas = MasterWeightage::withTrashed()->orderBy('deleted_at', 'asc')->get();

            $allJobLevels = [];
            
            $datas->map(function ($group) use ($allJobLevels) {
                $formData = json_decode($group->form_data, true); // Decode JSON data
            
                foreach ($formData as $item) {
                    // Store each item's jobLevel (if it exists) separately
                    if (isset($item['jobLevel'])) {
                        $allJobLevels = array_merge($allJobLevels, $item['jobLevel']);
                    }
                }

                $allJobLevels = array_unique($allJobLevels);
            
                // Assign this group's job levels as its own attribute
                $group->allJobLevels = $allJobLevels;
            });
            
            $parentLink = __('Settings');
            $link = __('Weightage');

            return view('pages.weightage.app', compact('datas', 'link', 'parentLink'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return response()->back()->withErrors($e);
        }
    }

    public function detail($id) {
        try {
            // Retrieve master weightage
            $datas = MasterWeightage::find($id);
            $data360s = MasterWeightage360::all();
            $formAppraisal = FormAppraisal::select('name', 'desc')->get();
            $allJobLevels = [];

            if ($datas) {
                $formData = json_decode($datas->form_data, true); // Decode JSON data
            
                foreach ($formData as $item) {
                    if (isset($item['jobLevel'])) {
                        $allJobLevels = array_merge($allJobLevels, $item['jobLevel']);
                    }
                }
            
                $allJobLevels = array_unique($allJobLevels); // Optional: remove duplicates
            
                // Add allJobLevels back to the $datas object if needed
                $datas->allJobLevels = $allJobLevels;
            }

            $group_company = EmployeeAppraisal::select('group_company')->distinct()->get();
            $job_level = EmployeeAppraisal::select('job_level')->distinct()->orderBy('job_level', 'asc')->get();
                        
            $parentLink = __('Weightage');
            $link = __('Details');

            return view('pages.weightage.detail', compact('datas', 'data360s', 'link', 'parentLink', 'group_company', 'formAppraisal', 'job_level'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function create() {
        try {
            // Retrieve master weightage
            $datas = MasterWeightage::all();
            $data360s = MasterWeightage360::all();
            $formAppraisal = FormAppraisal::select('name', 'desc')->get();
            $competency = MasterCompetencyType::select('id','competency_name')->orderBy('id', 'asc')->get();

            $group_company = EmployeeAppraisal::select('group_company')->distinct()->get();
            $job_level = EmployeeAppraisal::select('job_level')->distinct()->orderBy('job_level', 'asc')->get();
                        
            $parentLink = __('Weightage');
            $link = __('Create');

            $max_form = 10;

            return view('pages.weightage.create', compact('datas', 'data360s', 'link', 'parentLink', 'group_company', 'formAppraisal', 'job_level', 'max_form', 'competency'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function edit($id) {
        try {
            // Retrieve master weightage
            $datas = MasterWeightage::find($id);
            $data360s = MasterWeightage360::all();
            $formAppraisal = FormAppraisal::select('name', 'desc')->get();
            $competency = MasterCompetencyType::select('id','competency_name')->orderBy('id', 'asc')->get();
            $allJobLevels = [];

            if ($datas) {
                $formData = json_decode($datas->form_data, true); // Decode JSON data
            
                foreach ($formData as $item) {
                    if (isset($item['jobLevel'])) {
                        $allJobLevels = array_merge($allJobLevels, $item['jobLevel']);
                    }
                }
            
                $allJobLevels = array_unique($allJobLevels); // Optional: remove duplicates
            
                // Add allJobLevels back to the $datas object if needed
                $datas->allJobLevels = $allJobLevels;
            }

            $group_company = EmployeeAppraisal::select('group_company')->distinct()->get();
            $job_level = EmployeeAppraisal::select('job_level')->distinct()->orderBy('job_level', 'asc')->get();
                        
            $parentLink = __('Weightage');
            $link = __('Edit');

            $max_form = 10;

            return view('pages.weightage.edit', compact('datas', 'data360s', 'link', 'parentLink', 'group_company', 'formAppraisal', 'job_level', 'max_form', 'competency'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        // First validate the basic fields
        $validatedData = $request->validate([
            'period' => 'required',
            'group_company' => 'required',
            'number_assessment_form' => 'required|integer|min:1|max:10',
        ]);

        // Check if period and group_company combination exists (excluding current record)
        $existingWeightage = MasterWeightage::where('period', $validatedData['period'])
            ->where('group_company', $validatedData['group_company'])
            ->first();

        if ($existingWeightage) {
            return back()->with('error', 'Weightage configuration on '.$validatedData['group_company'].' and period '.$validatedData['period'].' already exists, please adjust your configuration');
        }

        // Initialize form data array
        $formData = [];

        // Loop through each assessment form
        for ($i = 0; $i < $request->number_assessment_form; $i++) {
            // Validate job levels for this form
            $jobLevels = $request->input("job_level.$i", []);
            if (empty($jobLevels)) {
                return back()->withErrors(["job_level.$i" => "Job levels are required for Assessment Form " . ($i + 1)]);
            }

            // Get competencies data for this form
            $competencies = [];
            
            // Get all weightage inputs for this form
            $weightageInputs = array_filter($request->all(), function($key) use ($i) {
                return strpos($key, "weightage-$i-") === 0;
            }, ARRAY_FILTER_USE_KEY);

            // Get all form names for this form
            $formNames = array_filter($request->all(), function($key) use ($i) {
                return strpos($key, "form-name-$i") === 0;
            }, ARRAY_FILTER_USE_KEY);

            // Calculate total weightage
            $totalWeightage = 0;
            foreach ($weightageInputs as $key => $weightage) {
                $index = substr($key, strrpos($key, '-') + 1);
                $competency = $request->input("competency-$i-$index"); // Make sure these fields exist in your form
                $formName = $request->input("form-name-$i$index");
                
                // Validate weightage
                if (!is_numeric($weightage) || $weightage < 0 || $weightage > 100) {
                    return back()->withErrors(["weightage-$i-$index" => "Invalid weightage value"]);
                }

                $totalWeightage += (int)$weightage;

                $competencyData = [
                    'competency' => $competency,
                    'formName' => $formName ?: 'KPI', // Default to KPI if not provided
                    'weightage' => (int)$weightage,
                ];

                // Add weightage360 if it exists
                $weightage360 = $request->input("weightage-360-$i-$index");

                $weightage360DecodedData = is_string($weightage360) ? json_decode($weightage360, true) : $weightage360;
                
                if ($weightage360) {
                    $competencyData['weightage360'] = json_decode($weightage360);
                }else{
                    $competencyData['weightage360'] = [];
                }

                $competencies[] = $competencyData;
            }

            // Validate total weightage
            if ($totalWeightage !== 100) {
                return back()->withErrors(["total-$i" => "Total weightage must be 100 for Assessment Form " . ($i + 1)]);
            }

            // Add form data
            $formData[] = [
                'jobLevel' => $jobLevels,
                '360' => false,
                'competencies' => $competencies
            ];
        }

        // Create the MasterWeightage record
        $masterWeightage = MasterWeightage::create([
            'period' => $validatedData['period'],
            'number_assessment_form' => $validatedData['number_assessment_form'],
            'group_company' => $validatedData['group_company'],
            'form_data' => json_encode($formData),
            'created_by' => Auth()->id()
        ]);

        return redirect()->route('admin-weightage')
            ->with('success', 'Weightage configuration saved successfully');
    }

    public function update(Request $request)
    {
        // First validate the basic fields
        $validatedData = $request->validate([
            'id' => 'required|integer',
            'number_assessment_form' => 'required|integer|min:1|max:10',
        ]);

        // Initialize form data array
        $formData = [];

        // Loop through each assessment form
        for ($i = 0; $i < $request->number_assessment_form; $i++) {
            // Validate job levels for this form
            $jobLevels = $request->input("job_level.$i", []);
            if (empty($jobLevels)) {
                return back()->withErrors(["job_level.$i" => "Job levels are required for Assessment Form " . ($i + 1)]);
            }

            // Get competencies data for this form
            $competencies = [];
            
            // Get all weightage inputs for this form
            $weightageInputs = array_filter($request->all(), function($key) use ($i) {
                return strpos($key, "weightage-$i-") === 0;
            }, ARRAY_FILTER_USE_KEY);

            // Get all form names for this form
            $formNames = array_filter($request->all(), function($key) use ($i) {
                return strpos($key, "form-name-$i") === 0;
            }, ARRAY_FILTER_USE_KEY);

            // Calculate total weightage
            $totalWeightage = 0;
            foreach ($weightageInputs as $key => $weightage) {
                $index = substr($key, strrpos($key, '-') + 1);
                $competency = $request->input("competency-$i-$index"); // Make sure these fields exist in your form
                $formName = $request->input("form-name-$i$index");
                
                // Validate weightage
                if (!is_numeric($weightage) || $weightage < 0 || $weightage > 100) {
                    return back()->withErrors(["weightage-$i-$index" => "Invalid weightage value"]);
                }

                $totalWeightage += (int)$weightage;

                $competencyData = [
                    'competency' => $competency,
                    'formName' => $formName, // Default to KPI if not provided
                    'weightage' => (int)$weightage,
                ];

                // Add weightage360 if it exists
                $weightage360 = $request->input("weightage360-$i-$index");
                if ($weightage360) {
                    $competencyData['weightage360'] = [
                        'employee' => 20,
                        'manager' => 30,
                        'peers' => 30,
                        'subordinate' => 20
                    ];
                }

                 // Add weightage360 if it exists
                $weightage360 = $request->input("weightage-360-$i-$index");

                $weightage360DecodedData = is_string($weightage360) ? json_decode($weightage360, true) : $weightage360;
                
                if ($weightage360) {
                    $competencyData['weightage360'] = json_decode($weightage360);
                }else{
                    $competencyData['weightage360'] = [];
                }
 
                 $competencies[] = $competencyData;
            }

            // Validate total weightage
            if ($totalWeightage !== 100) {
                return back()->withErrors(["total-$i" => "Total weightage must be 100 for Assessment Form " . ($i + 1)]);
            }

            // Add form data
            $formData[] = [
                'jobLevel' => $jobLevels,
                '360' => false,
                'competencies' => $competencies
            ];
        }

        // Update the MasterWeightage record
        MasterWeightage::where('id', $validatedData['id'])->update([
            'number_assessment_form' => $validatedData['number_assessment_form'],
            'form_data' => json_encode($formData),
            'updated_by' => Auth()->id()
        ]);
        

        return redirect()->route('admin-weightage')
            ->with('success', 'Weightage configuration saved successfully');
    }

    public function checkMasterWeightage(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
            'group_company' => 'required|string',
        ]);

        $exists = MasterWeightage::where('period', $request->period)
            ->where('group_company', $request->group_company)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function archive($id) {
        try {
            // Retrieve master weightage
            $datas = MasterWeightage::onlyTrashed()->findOrFail($id);
            $formAppraisal = FormAppraisal::select('name', 'desc')->get();
            $allJobLevels = [];

            if (Empty($datas)) {
                $formData = json_decode($datas->form_data, true); // Decode JSON data
            
                foreach ($formData as $item) {
                    if (isset($item['jobLevel'])) {
                        $allJobLevels = array_merge($allJobLevels, $item['jobLevel']);
                    }
                }
            
                $allJobLevels = array_unique($allJobLevels); // Optional: remove duplicates
            
                // Add allJobLevels back to the $datas object if needed
                $datas->allJobLevels = $allJobLevels;
            }

            $group_company = EmployeeAppraisal::select('group_company')->distinct()->get();
            $job_level = EmployeeAppraisal::select('job_level')->distinct()->orderBy('job_level', 'asc')->get();
                        
            $parentLink = __('Weightage');
            $link = __('Archived');

            return view('pages.weightage.archive', compact('datas', 'link', 'parentLink', 'group_company', 'formAppraisal', 'job_level'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function archiving(Request $request)
    { 
        try {
            $validatedData = $request->validate([
                'id' => 'required|integer',
            ]);

            // Find the MasterWeightage by ID
            $weightage = MasterWeightage::findOrFail($validatedData['id']);

            // Soft delete the weightage
            $weightage->delete();

            return response()->json(['message' => 'Weightage archived successfully!'], 200);

        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            return response()->json(['message' => 'Error archiving weightage: ' . $e->getMessage()], 500);
        }
    }
}
