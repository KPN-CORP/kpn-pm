<?php

namespace App\Http\Controllers;

use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\KpiUnits;
use App\Models\MasterCalibration;
use App\Models\MasterRating;
use App\Services\AppService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class RatingController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;
    protected $period;

    public function __construct(AppService $appService)
    {
        $this->user = Auth()->user()->employee_id;
        $this->appService = $appService;
        $this->category = 'Appraisal';
        $this->period = 2024;
    }

    public function index(Request $request) {
        try {
            $user = $this->user;
            $filterYear = $request->input('filterYear');

            // Get the KPI unit and calibration percentage
            $kpiUnit = KpiUnits::with(['masterCalibration'])->where('employee_id', $user)->first();

            if (!$kpiUnit) {
                Session::flash('error', "Your KPI unit data not found");
            }

            $calibration = $kpiUnit->masterCalibration->percentage;
    
            $emptyAppraisal = ApprovalLayerAppraisal::with(['employee', 'approvalRequest' => function ($query) {
                $query->with('manager');
            }])
            ->where('approver_id', $user)
            ->where('layer_type', 'calibrator')
            ->where(function ($query) use ($user) {
                $query->whereDoesntHave('approvalRequest') // No approvalRequest
                        ->orWhere(function ($subQuery) use ($user) {
                            $subQuery->whereDoesntHave('contributors', function ($contributorQuery) use ($user) {
                                $contributorQuery->where('contributor_id', $user)->where('contributor_type', 'manager');
                            });
                        });
            })->whereHas('approvalRequest', function ($query) {
                $query->where('status', '!=', 'Approved'); // Only include records where status is not 'Approved'
            })
            ->get();
    
            // Get master calibration and ratings
            $masterCalibration = MasterCalibration::with('masterRating')
                ->select('id_rating_group', 'period', 'percentage')
                ->where('id_rating_group', '1')
                ->where('period', 2024)
                ->get();
    
            $masterRating = MasterRating::select('id_rating_group', 'parameter', 'value', 'min_range', 'max_range')
                ->where('id_rating_group', '1')
                ->get();

            $allData = ApprovalLayerAppraisal::with(['employee'])
        ->where('approver_id', $user)
        ->where('layer_type', 'calibrator')
        ->get();

    // Query for ApprovalLayerAppraisal data with approval requests
    $dataWithRequests = ApprovalLayerAppraisal::join('approval_requests', 'approval_requests.employee_id', '=', 'approval_layer_appraisals.employee_id')
        ->where('approval_layer_appraisals.approver_id', $user)
        ->where('approval_layer_appraisals.layer_type', 'calibrator')
        ->where('approval_requests.category', $this->category)
        ->select('approval_layer_appraisals.*')
        ->get()
        ->keyBy('id');

    // Group the data
    $datas = $allData->groupBy(function ($data) {
        $jobLevel = $data->employee->job_level;
        if (in_array($jobLevel, ['2A', '2B', '3A', '3B'])) {
            return 'Level23';
        } elseif (in_array($jobLevel, ['4A', '4B', '5A', '5B'])) {
            return 'Level45';
        } elseif (in_array($jobLevel, ['6A', '6B', '7A', '7B'])) {
            return 'Level67';
        } elseif (in_array($jobLevel, ['8A', '8B', '9A', '9B'])) {
            return 'Level89';
        }
        return 'Other Levels';
    })->map(function ($group) use ($dataWithRequests, $user) {
        $withRequests = ApprovalLayerAppraisal::join('approval_requests', 'approval_requests.employee_id', '=', 'approval_layer_appraisals.employee_id')
            ->where('approval_layer_appraisals.approver_id', $user)
            ->where('approval_layer_appraisals.layer_type', 'calibrator')
            ->where('approval_requests.category', $this->category)
            ->whereIn('approval_layer_appraisals.id', $group->pluck('id'))
            ->select('approval_layer_appraisals.*', 'approval_requests.*')
            ->get()
            ->groupBy('id')
            ->map(function ($subgroup) {
                $appraisal = $subgroup->first();
                $appraisal->approval_request = $subgroup->first();
                return $appraisal;
            });

        return [
            'with_requests' =>  $withRequests->values(),
            'without_requests' => $group->filter(function ($item) use ($dataWithRequests) {
                return !$dataWithRequests->has($item->id);
            })
        ];
    });

    $ratingDatas = $datas->map(function ($group) use ($user) {
        // Process `with_requests`
        $withRequests = $group['with_requests']->map(function ($data) use ($user) {
            // Calculate the suggested rating
            $suggestedRating = $this->appService->suggestedRating($data->employee->employee_id, $this->category);
    
            $data->suggested_rating = $this->appService->convertRating($suggestedRating);

            $data->rating_value = $this->appService->ratingValue($data->employee->employee_id, $this->user, $this->period);
    
            // Check if the user is a calibrator for this employee
            $isCalibrator = Calibration::where('approver_id', $user)
                ->where('employee_id', $data->employee->employee_id)
                ->where('status', 'Pending')
                ->exists();
            $data->is_calibrator = $isCalibrator;
    
            // Check if rating is allowed for the employee
            $data->rating_allowed = $this->appService->ratingAllowedCheck($data->employee->employee_id);
    
            // Fetch the current calibrator if it exists
            $currentCalibrator = Calibration::with(['approver'])
                ->where('employee_id', $data->employee->employee_id)
                ->where('status', 'Pending')
                ->first();
    
            // Set current_calibrator or a default value
            if ($currentCalibrator && $currentCalibrator->approver) {
                $data->current_calibrator = $currentCalibrator->approver->fullname . ' (' . $currentCalibrator->approver->employee_id . ')';
            } else {
                $data->current_calibrator = false;
            }
    
            return $data;
        });
    
        // Process `without_requests`
        $withoutRequests = $group['without_requests']->map(function ($data) use ($user) {
            // Calculate the suggested rating
            $suggestedRating = $this->appService->suggestedRating($data->employee->employee_id, $this->category);
    
            // Since there are no approval requests, handle the suggested rating logic accordingly
            $data->suggested_rating = $this->appService->convertRating($suggestedRating);
    
            // Check if the user is a calibrator for this employee
            $isCalibrator = Calibration::where('approver_id', $user)
                ->where('employee_id', $data->employee->employee_id)
                ->where('status', 'Pending')
                ->exists();
            $data->is_calibrator = $isCalibrator;
    
            // Check if rating is allowed for the employee
            $data->rating_allowed = $this->appService->ratingAllowedCheck($data->employee->employee_id);
    
            // Fetch the current calibrator if it exists
            $currentCalibrator = Calibration::with(['approver'])
                ->where('employee_id', $data->employee->employee_id)
                ->where('status', 'Pending')
                ->first();
    
            // Set current_calibrator or a default value
            if ($currentCalibrator && $currentCalibrator->approver) {
                $data->current_calibrator = $currentCalibrator->approver->fullname . ' (' . $currentCalibrator->approver->employee_id . ')';
            } else {
                $data->current_calibrator = false;
            }
    
            return $data;
        });
    
        // Combine both `with_requests` and `without_requests` results
        return $withRequests->merge($withoutRequests);
    });
      
            // Get calibration results
            $calibrations = $datas->map(function ($group) use ($calibration) {
                $count = $group['with_requests']->count();
    
                // Calculate weighted and percentage results based on calibration
                $ratingResults = [];
                $percentageResults = [];
    
                $calibration = json_decode($calibration, true);
                foreach ($calibration as $key => $weight) {
                    $ratingResults[$key] = round($count * $weight);
                    $percentageResults[$key] = round(100 * $weight);
                }
    
                // Process suggested ratings
                $allApprovalRequests = $group['with_requests']->pluck('approvalRequest')->flatten();
                $suggestedRatingCounts = $allApprovalRequests->pluck('suggested_rating')->countBy();

    
                // Calculate total suggested ratings for percentages
                $totalSuggestedRatings = $suggestedRatingCounts->sum();
    
                // Combine calibration and suggested ratings dynamically
                $combinedResults = [];
                foreach ($calibration as $key => $weight) {
                    $ratingCount = $suggestedRatingCounts->get($key, 0);
    
                    // Calculate percentage relative to total
                    $ratingPercentage = $totalSuggestedRatings > 0
                        ? round(($ratingCount / $totalSuggestedRatings) * 100, 2)
                        : 0;
    
                    $combinedResults[$key] = [
                        'percentage' => $percentageResults[$key] . '%',
                        'rating_count' => $ratingResults[$key],
                        'suggested_rating_count' => $ratingCount,
                        'suggested_rating_percentage' => $ratingPercentage . '%'
                    ];
                }
    
                return [
                    'count' => $count,
                    'combined' => $combinedResults,
                ];
            });

            // dd($ratingDatas);
    
            // Determine the active level as the first non-empty level
            $activeLevel = null;
            foreach ($calibrations as $level => $data) {
                if (!empty($data)) {
                    $activeLevel = $level;
                    break;
                }
            }

    
            $parentLink = 'Calibration';
            $link = 'Rating';
    
            return view('pages.rating.app', compact('ratingDatas', 'calibrations', 'emptyAppraisal', 'masterRating', 'link', 'parentLink', 'activeLevel'));
    
        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());
    
            return view('pages.rating.app', [
                'data' => [],
                'link' => 'My Appraisal',
                'parentLink' => 'Appraisal',
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
    
    public function store(Request $request) {

        $validatedData = $request->validate([
            'approver_id' => 'required|string|size:11',
            'employee_id' => 'required|array',
            'appraisal_id' => 'required|array',
            'rating' => 'required|array',
        ]);

        $status = 'Approved';

        $employees = $validatedData['employee_id'];
        $appraisal_id = $validatedData['appraisal_id'];
        $rating = $validatedData['rating'];

        foreach ($employees as $index => $employee) {

            $nextApprover = $this->appService->processApproval('01117040008', $validatedData['approver_id']);

            $ratingData[$index] = [
                'employee_id' => $employee,
                'appraisal_id' => $appraisal_id[$index],
                'rating' => $rating[$index],
                'approver' => $nextApprover,
            ];

            $index++;
        }

        foreach ($ratingData as $rating) {
            $updated = Calibration::where('approver_id', $validatedData['approver_id'])
                ->where('employee_id', $rating['employee_id'])
                ->where('appraisal_id', $rating['appraisal_id'])
                ->where('period', $this->period)
                ->update([
                    'rating' => $rating['rating'],
                    'status' => $status,
                    'updated_by' => Auth()->user()->id
                ]);

            // Optionally, check if update was successful
            if ($updated) {
                $calibration = new Calibration();
                $calibration->appraisal_id = $rating['appraisal_id'];
                $calibration->employee_id = $rating['employee_id'];
                $calibration->approver_id = $rating['approver']['next_approver_id'];
                $calibration->period = $this->period;
                $calibration->created_by = Auth()->user()->id;
                $calibration->save();
            }else{
                return redirect('rating')->with('error', 'No record found for employee ' . $rating['employee_id'] . ' in period '.$this->period.'.');
            }
        }

        return redirect('rating')->with('success', 'Ratings submitted successfully.');
    }

}
