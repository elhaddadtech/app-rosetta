<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Imports\UsersUpdateImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller {

  public function importStudents(Request $request) {
    $file   = $request->file('csv_file');
    $import = new UsersImport();

    Excel::import($import, $file);

    if (!empty($import->errors)) {
      return response()->json([
        'status' => 'error',
        'errors' => $import->errors,
      ], 422);
    }

    return response()->json([
      'status'         => 'success',
      'imported_users' => $import->count,
      'message'        => 'CSV file imported successfully',
    ], 200);

  }

  public function importUpdateCSV(Request $request) {
    $file   = $request->file('csv_file');
    $import = new UsersUpdateImport();

    Excel::import($import, $file);

    if (!empty($import->errors)) {
      return response()->json([
        'status' => 'error',
        'errors' => $import->errors,
      ], 422);
    }

    return response()->json([
      'status'  => 'success',
      'message' => 'CSV file updated successfully',
    ], 200);

  }
}
