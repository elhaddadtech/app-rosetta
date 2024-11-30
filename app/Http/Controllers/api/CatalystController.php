<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Imports\CatalystAImport;
use App\Imports\CatalystImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CatalystController extends Controller {
  public function importCatalyst(Request $request) {
    // Validate file input

    // set_time_limit(120);
    $request->validate([
      'csv_file' => 'required|mimes:csv,txt',
    ]);
    try {
      // Import file
      $import = new CatalystImport();
      dd(Excel::import($import, $request->file('csv_file')));
      Excel::import($import, $request->file('csv_file'));
      dd('ok');

      // Get processed data (assuming CatalystImport saves results somewhere accessible)
      $processedData = session('students') ?? [];

      return response()->json([
        'message' => 'Fichier traité avec succès',
        'data'    => $processedData,
      ]);

    } catch (\Exception $e) {
      // Handle exceptions
      return response()->json([
        'message' => 'Erreur lors de l\'importation du fichier: ' . $e->getMessage(),
      ], 500);
    }

  }

  public function import(Request $request) {
    // ini_set('memory_limit', '1G');
    // $request->validate([
    //   'csv_file' => 'required|file|mimes:csv,txt',
    // ]);
    $fileName     = $request->file('csv_file')->getClientOriginalName();
    $filePath     = $request->file('csv_file')->storeAs('uploads', $fileName, 'public');
    $fileFullPath = storage_path('app/public/uploads/' . $fileName);

    $import = new CatalystAImport();

    Excel::import($import, $fileFullPath);

    // $emails = $import->getEmails();
    // dd('3mail');
    // $ok = new CatalystCUImport();

    return response()->json(['path' => $fileFullPath]);
  }

}
