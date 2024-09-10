<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLayerAppraisal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    protected $category;
    protected $user;

    public function __construct()
    {
        $this->user = Auth()->user()->employee_id;
        $this->category = 'Appraisal';
    }

    public function index(Request $request) {
        try {

            $user = $this->user;
            $filterYear = $request->input('filterYear');

            // actual query
            // $contributors = AppraisalContributor::with(['employee'])->where('contributor_id', $user)->get(); 
            
            // for test
            // $contributors = AppraisalContributor::with(['employee'])->where('employee_id', $user)->get();  
            
            $dataTeams = ApprovalLayerAppraisal::with(['approver', 'contributors', 'approvalRequest'])->where('approver_id', $user)->where('layer_type', 'manager')->get();

            $data360 = ApprovalLayerAppraisal::with(['approver', 'contributors', 'approvalRequest'])->where('approver_id', $user)->where('layer_type', '!=', 'manager')->get();

            // foreach ($datas as $data) {
            //     dd($data->contributors); // Dump and die for the first item's contributors
            // }
            $contributors = $data360->pluck('contributors');
            

            $parentLink = 'Calibration';
            $link = 'Rating';

            return view('pages.rating.app', compact('dataTeams', 'data360', 'contributors', 'link', 'parentLink'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
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
}
