<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LearnerGrowthExport implements FromCollection, WithHeadings {
  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection() {
    // Fetch distinct level_test_1 values from the results table
    $levels = DB::table('results')
      ->distinct()
      ->pluck('level_test_1')
      ->toArray();

    // Sort levels in desired order
    $sortedLevels = [
      'Below A1', 'A2', 'A1', 'B1', 'B2', 'C1', 'C1+',
    ];

    // Ensure levels are ordered correctly
    $orderedLevels = array_intersect($sortedLevels, $levels);

    return DB::table('results')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->select(
        'file as date_report',
        'level_test_1 as level',
        'languages.libelle as language_name',
        DB::raw('COUNT(DISTINCT student_id) as total_students'),
        // DB::raw("TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(total_time))), '%H:%i:%s') as total_hours")
      )
      ->whereIn('level_test_1', $orderedLevels)
      ->groupBy('file', 'level_test_1', 'languages.libelle')
      ->orderByRaw("FIELD(level_test_1, '" . implode("','", $orderedLevels) . "')")
      ->get();
  }

  /**
   * Define the headings for the export.
   */
  public function headings(): array {
    return [
      'Date Report',
      'Level',
      'Language Name',
      'Total Students',
      // 'Total Hours',
    ];
  }
}
