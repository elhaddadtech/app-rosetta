<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use Str;

class FondationReportController extends Controller {
  public $fileName  = '';
  public $dataExcel = [];
  public function importFondationCSV(Request $request) {
    $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

    $fileName     = $request->file('csv_file')->getClientOriginalName();
    $filePath     = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
    $fileFullPath = storage_path('app/public/' . $filePath);
    $file         = pathinfo($fileFullPath, PATHINFO_BASENAME);

    return response()->json(['message' => "CSV file {$file} imported successfully.", 'path' => $fileFullPath]);

  }

  public function handle(Request $request) {
    // Validate the incoming request to ensure the CSV path is provided
    $request->validate(['csv_path' => 'required']);
    // Get the basename (filename with extension)
    $fileWithExtension = pathinfo($request->csv_path, PATHINFO_BASENAME);

// Get the filename without the extension
    $this->fileName = pathinfo($fileWithExtension, PATHINFO_FILENAME);
    if (Course::where('file', strtolower($this->fileName))->exists()) {
      return response()->json(['message' => "CSV file {$fileWithExtension} already imported."]);

    }

    try {
      // Start logging the job process
      Log::info("Job started for file: {$request->csv_path}");

      // Process the CSV file and retrieve data
      $data = $this->processCSV($request->csv_path);

      // If no valid data found, log and return early
      if (empty($data)) {
        Log::warning('No valid data found in the CSV file.');

        return response()->json(['message' => 'No valid data found in the CSV file.'], 404);
      }

      // Initialize an array to hold all course data for bulk insert
      $coursesToInsert = [];

      // Group the data by language first
      $groupedByLangue = collect($data)->groupBy('langue')->map(function ($students, $langue) use (&$coursesToInsert) {
        // Group the students by email
        return $students->groupBy('email')->map(function ($userCourses, $email) use ($langue, &$coursesToInsert) {
          // Retrieve the result IDs based on the email and language
          $resultIds = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('languages', 'results.language_id', '=', 'languages.id')
            ->where('users.email', strtolower($email))
            ->where('languages.libelle', strtolower($langue))
            ->pluck('results.id');

          // Log and skip if no result IDs found
          if ($resultIds->isEmpty()) {
            Log::warning("No results found for email: {$email} and language: {$langue}");

            return []; // Skip this user if no results found
          }

          // Group the courses by their name
          $userCourses->groupBy('cours_name')->map(function ($lessons, $coursName) use ($resultIds, &$coursesToInsert) {
            $resultId = $resultIds->first(); // Use the first result ID (you can customize if needed)

            // Prepare the course data for insertion
            $coursesToInsert[] = [
              'result_id'      => $resultId,
              'cours_progress' => $lessons->pluck('cours_progress')->unique()->first() == '' ? 0 : $lessons->pluck('cours_progress')->unique()->first(), // Get the first unique course progress
              'cours_grade' => $lessons->pluck('cours_grade')->unique()->first() == '' ? 0 : $lessons->pluck('cours_grade')->unique()->first(), // Get the first unique course grade
              'cours_name' => $coursName,
              'file'           => $this->fileName,
              // Calculate unique lessons
              // 'total_lessons'  => $lessons->pluck('lesson_name')->unique()->count(), // Calculate unique lessons
            ];
          });

          return $userCourses;
        });
      });

      // Perform batch insert
      DB::transaction(function () use ($coursesToInsert) {
        $maxPlaceholders = 65535;
        $fieldsPerRow    = 22; // Adjust this to the actual number of fields you're inserting
        $maxRows         = floor($maxPlaceholders / $fieldsPerRow);
        $chunks          = array_chunk($coursesToInsert, $maxRows);

        // Optional: Log chunk sizes if necessary for debugging
        // file_put_contents(storage_path('learnerGrowth/failed_chunks.json'), json_encode($chunks));

        // Insert each chunk
        foreach ($chunks as $chunk) {
          Course::insert($chunk);
        }
      });

      return response()->json([
        'message' => 'Data processed and stored successfully.',
      ]);
    } catch (\Exception $e) {
      // Log any errors that occur
      Log::error("Job failed with exception: {$e->getMessage()}", [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      // Return a failure response with the error message

      return response()->json([
        'message' => 'Job failed',
        'error'   => $e->getMessage(),
      ], 500);
    }
  }

  private function processCSV($path) {
    if (!file_exists($path)) {
      Log::error("File not found: {$path}");

      return [];
    }
    // dd($path);
    $rows           = [];
    $desiredColumns = [2, 8, 9, 10, 11]; // Specify the indices of the columns you want to extract

    if (($handle = fopen($path, 'r')) !== false) {
      // Skip the first line (header row)
      fgetcsv($handle, 1000, ',');

      // Now process the remaining rows
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        if (!empty($data)) {
          // Extract only the columns you need
          $filteredRow = array_intersect_key($data, array_flip($desiredColumns));
          $rows[]      = $filteredRow;
        }
      }
      fclose($handle);
    }
    $header = array_shift($rows);
    // Output the rows (you can use dd() for debugging or return them)
    // dd($header);
    if (!$header) {
      Log::error('CSV file is empty or header is missing.');

      return [];
    }

    $indices = [
      $emailIndex = array_search('Email', $header),
      $LangueIndex = array_search('Language Level', $header),

      $CoursNameIndex = array_search('Language Level', $header),
      $CoursProgressIndex = array_search('Progress', $header),
      $CoursGradeIndex = array_search('Overall Score', $header),
      $LessonNameIndex = array_search('Current Activity', $header),
    ];

    $missingHeaders = array_filter($indices, fn($index) => $index === false);
    if (!empty($missingHeaders)) {
      Log::error('Missing required headers: ' . implode(', ', array_keys($missingHeaders)));

      return [];
    }

    $extractedData = [];

    foreach ($rows as $row) {
      $extractedData[] = [
        'email'          => isset($row[$emailIndex]) ? $row[$emailIndex] : null,
        'langue'         => isset($row[$LangueIndex]) && Str::contains($row[$LangueIndex], ' ')
        ? trim(Str::before($row[$LangueIndex], ' '))
        : (isset($row[$LangueIndex]) ? trim($row[$LangueIndex]) : ''),
        'cours_name'     => isset($row[$LangueIndex])
        ? implode(' ', array_slice(explode(' ', $row[$LangueIndex]), -2)) // Get last two words
        : '',
        'cours_progress' => isset($row[$CoursProgressIndex]) ? $row[$CoursProgressIndex] : null,
        'cours_grade'    => isset($row[$CoursGradeIndex]) ? $row[$CoursGradeIndex] : null,
        'lesson_name'    => isset($row[$LessonNameIndex]) ? $row[$LessonNameIndex] : null,
      ];
    }

    // dd($extractedData);

    return (array_filter($extractedData, fn($data) => !empty($data['email'])));
  }

}
