<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Achievement;

class ImportKpiController extends Controller
{
    public function showImportKpiForm()
    {
        $userId = Auth::id();
        $user   = Auth::user();
        $parentLink = 'Settings';
        $link = 'KPI Import';
        
        $query = Achievement::whereNull('deleted_at')
            ->with('employee')
            ->orderBy('updated_at', 'desc');

        if ($user->role !== 'superadmin') {
            $query->where('created_by', $user->id);
        }

        $achievements = $query->get();
                            
        return view('pages.kpi-admin.import-kpi', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'achievements' => $achievements,
        ]);
    }

    public function importKpi(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            // load file Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathName());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray();

            // remove header row
            $header = array_shift($rows);
            $errors = [];

            foreach ($rows as $row) {
                if (empty($row[0])) {
                    continue; // skip baris kosong
                }

                $employeeId = $row[0];
                $period     = $row[1];

                $months = [
                    "January", "February", "March", "April",
                    "May", "June", "July", "August",
                    "September", "October", "November", "December"
                ];

                $data = [];
                foreach ($months as $index => $month) {
                    $data[] = [
                        "month" => $month,
                        "value" => $row[$index + 2] ?? null,
                    ];
                }

                $achievement = Achievement::withTrashed()
                    ->where('employee_id', $employeeId)
                    ->where('period', $period)
                    ->first();

                if ($achievement) {
                    $achievement->restore();
                    $achievement->update([
                        'data'       => json_encode($data),
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                        'deleted_by' => null,
                    ]);
                } else {
                    Achievement::create([
                        'employee_id' => $employeeId,
                        'period'      => $period,
                        'data'        => json_encode($data),
                        'created_by'  => Auth::id(),
                        'created_at'  => now(),
                    ]);
                }
            }

            return back()->with('success', 'Import KPI berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $achievement = Achievement::findOrFail($id);
        $achievement->deleted_by = Auth::id();
        $achievement->deleted_at = now();
        $achievement->save();

        return redirect()->route('importkpi')->with('success', 'Data berhasil dihapus.');
    }
}
