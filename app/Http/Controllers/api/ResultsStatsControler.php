<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use DB;

class ResultsStatsControler extends Controller {

  public function exportLearnerGrowthToCsv() {
    // Get the latest report date
    $latestReportDate = DB::table('results')->max('results.file');

    // Get the results for the latest report date, including total students per language
    $results = DB::table('results')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->select(
        'results.file as report_file',
        'level_test_1 as level',
        'languages.libelle as language_name',
        DB::raw('COUNT(DISTINCT student_id) as total_students'),
        DB::raw('(SELECT COUNT(DISTINCT student_id) FROM results WHERE file = results.file) as total_students_overall')
      )
      ->where('results.file', '=', $latestReportDate)
      ->whereIn('level_test_1', ['Below A1', 'A1', 'A2', 'B1', 'B2', 'C1', 'C1+'])
      ->groupBy('results.file', 'level_test_1', 'languages.libelle')
      ->orderByRaw("FIELD(level_test_1, 'Below A1', 'A1', 'A2', 'B1', 'B2', 'C1', 'C1+')")
      ->get();

    // Define the CSV file name and path
    $fileName = $latestReportDate . '.csv';
    $filePath = storage_path($fileName);

    // Open the file for writing
    $handle = fopen($filePath, 'w');

    // Add BOM for UTF-8 encoding (for Excel compatibility)
    fwrite($handle, "\xEF\xBB\xBF");

    // Write the CSV header
    fputcsv($handle, [
      'Report File',
      'Language',
      'Level',
      'Total Students',
    ]);

    // Write the data rows for each language and level
    foreach ($results as $row) {
      fputcsv($handle, [
        $row->report_file,
        $row->language_name,
        $row->level,
        $row->total_students,
      ]);
    }

    // Add a blank row before the overall total
    fputcsv($handle, []);

    // Add the overall total students count
    fputcsv($handle, ['Total Students Overall', '', '', $results->first()->total_students_overall]);

    // Close the file after writing
    fclose($handle);

    // Return the file as a downloadable response and delete it after sending

    return response()->download($filePath)->deleteFileAfterSend(true);
  }
}
