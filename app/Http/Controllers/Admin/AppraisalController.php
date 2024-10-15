<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\Calibration;
use App\Models\EmployeeAppraisal;
use Illuminate\Http\Request;

use function Pest\Laravel\json;

class AppraisalController extends Controller
{
    public function index(Request $request)
    {
        $period = 2024;

        $query = EmployeeAppraisal::with(['appraisal' => function($query) use ($period) {
                $query->where('period', $period);
            }, 'appraisalLayer.approver', 'appraisalContributor', 'calibration']);

        $datas = $query->get()->map(function ($employee) {
            $approvalStatus = [];

            foreach ($employee->appraisalLayer as $layer) {
                if ($layer->layer_type !== 'manager') {
                    if (!isset($approvalStatus[$layer->layer_type])) {
                        $approvalStatus[$layer->layer_type] = [];
                    }

                    // Check availability depending on the layer_type (AppraisalContributor for peers/subordinates, Calibration for calibrators)
                    if ($layer->layer_type === 'calibrator') {
                        // Check using Calibration model for calibrators
                        $isAvailable = Calibration::where('approver_id', $layer->approver_id)
                            ->where('employee_id', $employee->employee_id)
                            ->where('status', 'Approved')
                            ->exists();
                    } else {
                        // Check using AppraisalContributor model for peers and subordinates
                        $isAvailable = AppraisalContributor::where('contributor_id', $layer->approver_id)
                            ->where('contributor_type', '!=', 'manager')
                            ->where('employee_id', $employee->employee_id)
                            ->exists();
                    }

                    // Append approver_id, layer, and status data to the corresponding array
                    $approvalStatus[$layer->layer_type][] = [
                        'approver_id' => $layer->approver_id,
                        'layer' => $layer->layer,
                        'status' => $isAvailable ? true : false,
                        'approver_name' => $layer->approver->fullname,
                        'approver_id' => $layer->approver->employee_id,
                    ];
                }
            }

            // Sort each layer_type's array by 'layer'
            foreach ($approvalStatus as $type => $layers) {
                usort($layers, function ($a, $b) {
                    return $a['layer'] <=> $b['layer'];
                });
                $approvalStatus[$type] = $layers;
            }

            // Prepare popover content
            $popoverContent = [];
            
            // Add calibrator layers
            foreach ($approvalStatus['calibrator'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "L" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Add peer layers
            foreach ($approvalStatus['peers'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "P" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Add subordinate layers
            foreach ($approvalStatus['subordinate'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "S" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Join content with line breaks
            $popoverText = implode("<br>", $popoverContent);

            $appraisal = $employee->appraisal && $employee->appraisal->first() 
                            ? $employee->appraisal->first()->id 
                            : null;

            return [
                'id' => $employee->employee_id,
                'name' => $employee->fullname,
                'approvalStatus' => $approvalStatus,
                'finalScore' => $appraisal 
                ? $this->calculateFinalScore($employee->employee_id, $appraisal) 
                : '-',
                'popoverContent' => $popoverText, // Add popover content here
            ];
        });

        $parentLink = __('Reports');
        $link = __('Appraisal');

        return view('pages.appraisals.admin.app', compact('datas', 'link', 'parentLink'));
    }


    private function calculateFinalScore($employeeId, $appraisalId)
    {
        // Retrieve the score, checking if a rating exists for the given employee and appraisal
        $score = Appraisal::select('id', 'employee_id', 'rating')
            ->where('employee_id', $employeeId)
            ->where('id', $appraisalId)
            ->whereNotNull('rating')
            ->first();

        // Return the rating if it exists, otherwise return '-'
        return $score ? $score->rating : '-';
    }

}
