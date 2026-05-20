<?php

namespace App\Http\Controllers;

use App\Imports\AchievementDataImport;
use App\Models\AchievementImportTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class ImportAchievementController extends Controller
{
    public function showImportForm()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Achievement Import';
        
        $goals_imports = AchievementImportTransaction::where('submit_by',$userId)->orderBy('created_at', 'desc')->get();
                            
        return view('pages.imports.import-achievement', [
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
            $import = new AchievementDataImport($filePath);

            Excel::import($import, $filePath);

            $import->saveToDatabase();

            $import->saveTransaction();

            Log::info('Data imported successfully.');
        } catch (\Exception $e) {
            Log::error("Import failed: " . $e->getMessage());
            return back()->with('error', "Import failed: " . $e->getMessage());
        }
        $queries = DB::getQueryLog();
        Log::info("Executed queries import Achievement admin: ", $queries);
        // Redirect dengan pesan sukses
        return redirect()->back()->with('success', 'Achievement imported successfully!');
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
