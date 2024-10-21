<?php

namespace App\Http\Controllers;

use App\Models\MasterCalibration;
use App\Models\MasterRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;

class CalibrationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Calibration';
        // $ratings = Rating::orderBy('created_at', 'desc')->get();
        $calibrations = MasterCalibration::with('createdBy')->orderBy('created_at', 'desc')->get();
        $calibrations = MasterCalibration::select('id_calibration_group','master_calibrations.name as name','users.name as created_by_name','users.id as created_by', DB::raw("GROUP_CONCAT(CONCAT('<b>',grade, '</b> - ', percentage) SEPARATOR ' <br> ') as detail"))
        ->leftJoin('users', 'master_calibrations.created_by', '=', 'users.id')
        ->groupBy('id_calibration_group', 'master_calibrations.name', 'users.name', 'users.id')
        ->orderBy('master_calibrations.created_at', 'desc')->get();
        
        return view('pages.master-calibration.app', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'calibrations' => $calibrations,
            'userId' => $userId,
        ]);
    }

    public function create()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Calibration';
        $sublink = 'Create Rating';
        $ratings = MasterRating::select('id_rating_group', DB::raw('MAX(rating_group_name) as rating_group_name'))
                        ->whereNull('deleted_at')
                        ->groupBy('id_rating_group')
                        ->orderBy('rating_group_name', 'asc')
                        ->get();

        return view('pages.master-calibration.create', [
            'link' => $link,
            'parentLink' => $parentLink,
            'sublink' => $sublink,
            'userId' => $userId,
            'ratings' => $ratings,
        ]);
    }
    public function show(Request $request)
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Calibration';
        $sublink = 'Create Rating';

        $value_calibration_name = $request->calibration_name;
        $value_kpi_unit = $request->kpi_unit;
        $value_indi_kpi = $request->individual_kpi;

        $ratings = MasterRating::select('id_rating_group', DB::raw('MAX(rating_group_name) as rating_group_name'))
                        ->whereNull('deleted_at')
                        ->groupBy('id_rating_group')
                        ->orderBy('rating_group_name', 'asc')
                        ->get();

        $validatedData = $request->validate([
            'calibration_name' => 'required|string|max:255',
            'kpi_unit' => 'required|string',
            'individual_kpi' => 'required|string',
        ]);
    
        $kpiUnits = MasterRating::where('id_rating_group', $request->kpi_unit)->pluck('parameter'); 
        $jumlahKpiUnits = $kpiUnits->count();

        $individualKpis = MasterRating::where('id_rating_group', $request->individual_kpi)->pluck('parameter');
    
        return view('pages.master-calibration.create', compact('kpiUnits', 'individualKpis', 'validatedData', 'ratings', 'link', 'parentLink', 'sublink', 'jumlahKpiUnits', 'value_calibration_name', 'value_kpi_unit', 'value_indi_kpi'));
    }

    public function formupdate(string $id)
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Calibration';
        $sublink = 'Update Rating';
        $id_calibration_group = Crypt::decryptString($id);
        // dd($id_calibration_group);
        $firstCalibration = MasterCalibration::where('id_calibration_group', $id_calibration_group)->first();
        $fetchCalibration = MasterCalibration::where('id_calibration_group', $id_calibration_group)->get();

        foreach ($fetchCalibration as $detailcalibration) {
            // Grade sebagai kunci dan percentage sebagai nilai
            $calibrationPercentages[$detailcalibration->grade] = json_decode($detailcalibration->percentage, true);
        }
        $value_calibration_id = $firstCalibration->id_calibration_group;
        $value_calibration_name = $firstCalibration->name;
        $value_kpi_unit = $firstCalibration->kpi_unit;
        $value_indi_kpi = $firstCalibration->individual_kpi;

        $ratings = MasterRating::select('id_rating_group', DB::raw('MAX(rating_group_name) as rating_group_name'))
                        ->whereNull('deleted_at')
                        ->groupBy('id_rating_group')
                        ->orderBy('rating_group_name', 'asc')
                        ->get();
    
        $kpiUnits = MasterRating::where('id_rating_group', $firstCalibration->kpi_unit)->pluck('parameter'); 
        $jumlahKpiUnits = $kpiUnits->count();

        $individualKpis = MasterRating::where('id_rating_group', $firstCalibration->individual_kpi)->pluck('parameter');
    
        return view('pages.master-calibration.update', compact('kpiUnits', 'individualKpis', 'ratings', 'link', 'parentLink', 'sublink', 'jumlahKpiUnits', 'value_calibration_name', 'value_kpi_unit', 'value_indi_kpi','fetchCalibration','calibrationPercentages','value_calibration_id'));
    }

    public function store(Request $request) {

        $userId = Auth::id();
        $idCalibrationGroup = Str::uuid();
        $calibrationName    = $request->calibration_name;
        $kpi_unit           = $request->kpi_unit;
        $individual_kpi     = $request->individual_kpi;
        
        // // return redirect()->back()->with('success', 'Calibration data saved successfully.');
        $inputData = $request->all();
        // dd($inputData);
        if(isset($inputData['Xx'])) {
            foreach ($inputData['Xx'] as $kpiUnit => $kpiIndividuValues) {
                $detailKpiUnit = [];
                
                foreach ($kpiIndividuValues as $kpiIndividu => $value) {
                    $convert_percen = $value / 100;
                    $detailKpiUnit[$kpiIndividu] = $convert_percen;
                }
        
                $percentage = json_encode($detailKpiUnit);
                MasterCalibration::create([
                    'id_calibration_group' => $idCalibrationGroup,
                    'id_rating_group' => $individual_kpi,
                    'kpi_unit' => $kpi_unit,
                    'individual_kpi' => $individual_kpi,
                    'name' => $calibrationName, 
                    'grade' => $kpiUnit,
                    'percentage' => $percentage,
                    'created_by' => $userId,
                ]);
            }
            return redirect('admcalibrations')->with('success', 'Ratings submitted successfully.');
        }
    }
    public function update(Request $request) {

        $userId = Auth::id();
        $idCalibrationGroup = $request->idcalibration;
        $calibrationName    = $request->calibration_name;
        $kpi_unit           = $request->kpi_unit;
        $individual_kpi     = $request->individual_kpi;
        
        // // return redirect()->back()->with('success', 'Calibration data saved successfully.');
        $inputData = $request->all();

        if(isset($inputData['Xx'])) {
            foreach ($inputData['Xx'] as $kpiIndividu => $kpiUnitValues) {
                foreach ($kpiUnitValues as $kpiUnit => $value) {
                    // echo "KPI Individu: $kpiIndividu, KPI Unit: $kpiUnit, Value: $value <br>";
                    $convert_percen = $value/100;
                    $detailKpiUnit[$kpiUnit] = $convert_percen;
                    $percentage = json_encode($detailKpiUnit);
                }
                MasterCalibration::where('id_calibration_group', $idCalibrationGroup)
                ->where('grade', $kpiIndividu)
                ->update([
                    'kpi_unit' => $kpi_unit,
                    'individual_kpi' => $individual_kpi,
                    'name' => $calibrationName, 
                    'percentage' => $percentage,
                ]);
            }
            return redirect('admcalibrations')->with('success', 'Ratings submitted successfully.');
        }
    }
    public function destroy($id)
    {
        $calibrations = MasterCalibration::where('id_calibration_group', $id);
        // dd($calibrations);
        if ($calibrations->exists()) {
            $calibrations->delete();
        }

        return redirect()->route('admcalibrations')->with('success', 'Calibration deleted successfully.');
    }
}
