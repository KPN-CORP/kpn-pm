<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GoalsDataImport;
use App\Models\GoalsImportTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ImportGoalsController extends Controller
{
    /**
     * Menampilkan form untuk upload file Excel.
     */
    public function showImportForm()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Goals Import';
        // $today = Carbon::today();
        // $schedules = Schedule::with('createdBy')->get();
        // $schedulemasterpa = schedule::where('event_type','masterschedulepa')
        //                     ->whereDate('start_date', '<=', $today)
        //                     ->whereDate('end_date', '>=', $today)
        //                     ->orderBy('created_at')
        //                     ->first();
        $goals_imports = GoalsImportTransaction::where('submit_by',$userId)->orderBy('created_at')->get();
                            
        return view('pages.goals-admin.import-goals', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'goals_imports' => $goals_imports,
        ]);
    }

    public function import(Request $request)
    {
        // Validasi file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        // Pastikan file terupload
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store($path='public/uploads');
            Log::info("File uploaded successfully: " . $filePath);
        } else {
            Log::error("File upload failed."); 
            return back()->with('error', "File upload failed.");
        }
        DB::enableQueryLog();
        // Jalankan proses impor
        try {
            $import = new GoalsDataImport($filePath);
            Excel::import($import, $filePath);

            // Simpan data ke database setelah semua baris diproses
            $import->saveToDatabase();

            // Simpan transaksi
            $import->saveTransaction();
            Log::info("Data imported successfully.");
        } catch (\Exception $e) {
            Log::error("Import failed 2: " . $e->getMessage());
            return back()->with('error', "Import failed 2: " . $e->getMessage());
        }
        $queries = DB::getQueryLog();
        Log::info("Executed queries import goals admin: ", $queries);
        // Redirect dengan pesan sukses
        return redirect()->back()->with('success', 'Goals imported successfully!');
    }

    public function downloadExcel($file)
    {
        $filePath = storage_path($file); // Pastikan file berada di folder storage/app/uploads

        // Cek jika file ada
        if (file_exists($filePath)) {
            // Mengembalikan file untuk diunduh
            return Response::download($filePath);
        }

        // Jika file tidak ditemukan, tampilkan error 404
        abort(404, 'File not found');
    }
}
