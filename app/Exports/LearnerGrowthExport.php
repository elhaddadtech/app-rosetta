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

    // If needed, sort the levels as per your desired order
    $sortedLevels = [
      'Below A1', 'A2', 'A1', 'B1', 'B2', 'C1', 'C1+',
    ];

    // Ensure the fetched levels are in the same order
    $orderedLevels = array_intersect($sortedLevels, $levels);

    return DB::table('results')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->select(
        'file as date_report',
        'level_test_1 as level',
        'languages.libelle as language_name',
        DB::raw('COUNT(DISTINCT student_id) as total_students')
      )
      ->whereIn('level_test_1', $orderedLevels)
      ->groupBy('file', 'level_test_1', 'languages.libelle')
      ->orderByRaw("FIELD(level_test_1, '" . implode("','", $orderedLevels) . "')")
      ->get();
  }

  /**
   * Define the headings for the export
   */
  public function headings(): array {
    return [
      'Date Report',
      'Language Name',
      'Level',
      'Total Students',
    ];
  }
}
