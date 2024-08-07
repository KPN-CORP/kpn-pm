<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\Goal;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use stdClass;

use function PHPUnit\Framework\isEmpty;

class Appraisal360 extends Controller
{
    protected $category;
    protected $user;

    public function __construct()
    {
        $this->user = Auth()->user()->employee_id;
        $this->category = 'Appraisals';
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

            $user = $this->user;
            $filterYear = $request->input('filterYear');

            // actual query
            // $contributors = AppraisalContributor::with(['employee'])->where('contributor_id', $user)->get(); 
            
            // for test
            // $contributors = AppraisalContributor::with(['employee'])->where('employee_id', $user)->get();  
            
            $contributors = ApprovalLayerAppraisal::with(['approver'])->where('approver_id', $user)->get();

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

            $parentLink = 'Appraisals';
            $link = 'Appraisal 360°';

            return view('pages.appraisals-360.app', compact('datas', 'contributors', 'link', 'parentLink'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals-360.app', [
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

    public function initiate(Request $request)
    {
        $user = $this->user;

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        $goal = Goal::with(['employee'])->where('employee_id', $request->id)->whereYear('created_at', $year)->first();

        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/testFormGroup360.json');

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
        
        
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];

        // return response()->json($goal);
        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });
        
        $parentLink = 'Appraisals';
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-360.create', compact('step', 'parentLink', 'link', 'filteredFormData', 'formGroupData', 'goal', 'approval', 'goalData', 'user'));
    }

}
