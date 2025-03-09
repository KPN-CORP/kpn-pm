<?php

namespace App\Http\Controllers;

use App\Imports\AdminRatingImport;
use App\Models\ImportRatingTransaction;
use App\Models\KpiUnits;
use App\Models\MasterCalibration;
use App\Models\MasterRating;
use App\Services\AppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminImportController extends Controller
{
    protected $user;
    protected $appService;
    protected $period;

    public function __construct(AppService $appService)
    {
        $this->user = Auth()->user()->employee_id;
        $this->appService = $appService;
        $this->period = $this->appService->appraisalPeriod();
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $parentLink = 'Admin';
        $link = 'Imports';
        
        $datas = ImportRatingTransaction::with(['employee' => function($q) {
            $q->select('id', 'fullname', 'employee_id', 'designation_name');
        }])->where('created_by', $userId)->orderBy('created_at', 'desc')->get();
        
        return view('pages.imports.rating', [
            'link' => $link,
            'parentLink' => $parentLink,
            'datas' => $datas,
            'userId' => $userId,
        ]);
    }

    public function storeRating(Request $request)
    {

        // Validate incoming request
        $validatedData = $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv',
            'period' => 'required|string|size:4',
            'desc' => 'required'
        ]);

        $period = $validatedData['period'];
        $desc = $validatedData['desc'];
        $userId = Auth::id(); // Get current user ID
        $filename = 'rating_import_' . time() . '_' . $userId;

        // Handle file upload and store in 'public' directory
        $file = $request->file('excelFile');
        $fileExtension = $file->getClientOriginalExtension();

        $path = $file->storeAs('imports/' . $userId, $filename . '.' .$fileExtension, 'public');
        
        // Load master calibration data for the specified period
        try {
            // Fetch master calibration with validation
            $masterCalibration = MasterCalibration::with(['masterRating' => function($q) {
                $q->select('id_rating_group', 'parameter', 'value');
            }])->where('period', $period)->first();

            // Validation: Ensure Master Calibration exists
            if (!$masterCalibration) {
                return redirect()->route('importRating')->with('error', 'There is no Calibration for period ' . $period);
            }

            // Validation: Ensure it has master ratings
            if ($masterCalibration->masterRating->isEmpty()) {
                return redirect()->route('importRating')->with('error', 'No rating parameters found for the selected Master Calibration.');
            }

            // Get allowed rating parameters
            $allowedRating = $masterCalibration->masterRating->pluck('parameter')->toArray();

            // Initialize the import process
            $import = new AdminRatingImport($userId, $allowedRating, $period);
            Excel::import($import, $file);

            // Retrieve any invalid employees
            $invalidEmployees = $import->getInvalidEmployees();
            $errorPath = null;
            
            // If invalid entries exist, save invalid employees and return error message
            if (!empty($invalidEmployees)) {
                // Save invalid employee details to a file or database
                $errorPath = $import->saveInvalidEmployees($filename);

                // Create import transaction with error status
                $this->saveImportTransaction($period, $path, 'error', $userId, $errorPath['file_name'], $desc, $filename, $errorPath['file_path']);

                return redirect()->route('importRating')->with('error', 'There were some invalid entries during the import process.');
            }

            // If no invalid entries, mark the import as successful
            $this->saveImportTransaction($period, $path, 'success', $userId, '', $desc, $filename, '');

            return redirect()->route('importRating')->with('success', 'The import was successful.');

        } catch (ValidationException $e) {
            // Handle validation exception
            return redirect()->route('importRating')->with('error', 'Invalid file format or missing fields.');
        } catch (\Exception $e) {
            // Handle general exception
            return redirect()->route('importRating')->with('error', 'An error occurred during the import process.');
        }
    }

    /**
     * Save import transaction details to the database
     */
    private function saveImportTransaction($period, $path, $status, $userId, $errorFile, $desc, $fileName, $errorPath)
    {

        $model = new ImportRatingTransaction([
            'period' => $period,
            'path' => $path,
            'status' => $status,
            'created_by' => $userId,
            'error_files' => $errorFile,
            'desc' => $desc,
            'file_name' => $fileName,
            'error_path' => $errorPath,
        ]);

        $model->save();
    }

}
