<?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultsExport implements FromQuery, WithHeadings, WithChunkReading, ShouldAutoSize {

  // Define the chunk size for reading data in chunks
  public function query() {
    return DB::table('results')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->join('students', 'results.student_id', '=', 'students.id')
      ->join('users', 'students.user_id', '=', 'users.id') // Join with users table
      ->join('institutions', 'students.institution_id', '=', 'institutions.id') // Join with institutions table
      ->select([
        'results.file',
        'users.last_name',
        'users.first_name',
        'users.email as user_email',
        'languages.libelle as language_libelle',
        'institutions.libelle as institution_libelle', // Select the institution name
        'results.level_test_1',
        'results.score_test_1',
        'results.type_test_1',
        'results.date_test_1',
        'results.test_time_1',
        'results.desktop_time',
        'results.mobile_time',
        'results.total_time',
        'results.type_test_2',
        'results.date_test_2',
        'results.score_test_2',
        'results.level_test_2',
        'results.type_test_3',
        'results.date_test_3',
        'results.score_test_3',
        'results.level_test_3',
      ])
      ->orderBy('results.file'); // Or any other field you prefer
  }

  // Define the headings for the Excel file
  public function headings(): array {
    return [
      'File',
      'Last Name',
      'First Name',
      'Email',
      'Language',
      'Institution', // Add institution column header
      'Level Test 1',
      'Score Test 1',
      'Type Test 1',
      'Date Test 1',
      'Test Time 1',
      'Desktop Time',
      'Mobile Time',
      'Total Time',
      'Type Test 2',
      'Date Test 2',
      'Score Test 2',
      'Level Test 2',
      'Type Test 3',
      'Date Test 3',
      'Score Test 3',
      'Level Test 3',
    ];
  }

  // Specify the chunk size for reading data in chunks
  public function chunkSize(): int {
    return 500; // Process 500 records per chunk (you can adjust this number)
  }

  // Optional: Apply any additional performance optimizations like eager loading or reducing unnecessary columns
  // public function withData() {
  //   return $this->query()->with('relations'); // Example if you want to eager load
  // }
}
