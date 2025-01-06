 <?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultsExport implements FromQuery, WithHeadings, WithChunkReading, ShouldAutoSize {
  // Define the chunk size
  public function query() {
    // Use a raw SQL query to fetch results
    return DB::table('results')
      ->select([
        'type_test_1', 'date_test_1', 'score_test_1', 'level_test_1', 'test_time_1',
        'desktop_time', 'mobile_time', 'total_time',
        'type_test_2', 'date_test_2', 'score_test_2', 'level_test_2',
        'type_test_3', 'date_test_3', 'score_test_3', 'level_test_3',
        'student_id', 'language_id', 'file',
      ])->orderBy('id');
  }

  // Define the headings for the Excel file
  public function headings(): array {
    return [
      'Type Test 1', 'Date Test 1', 'Score Test 1', 'Level Test 1', 'Test Time 1',
      'Desktop Time', 'Mobile Time', 'Total Time',
      'Type Test 2', 'Date Test 2', 'Score Test 2', 'Level Test 2',
      'Type Test 3', 'Date Test 3', 'Score Test 3', 'Level Test 3',
      'Student ID', 'Language ID', 'File',
    ];
  }

  // Specify the chunk size for reading data in chunks
  public function chunkSize(): int {
    return 500; // Process 1000 records per chunk
  }

  // // Apply styles (e.g., make the first row bold)
  // public function styles($sheet) {
  //   $sheet->getStyle('A1:Z1')->getFont()->setBold(true); // Make the headers bold
  // }
}
