<?php

namespace App\Http\Controllers\api;

use App\Exports\CouresExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class BuilderController extends Controller {
  public $fileName  = '';
  public $dataExcel = [];
  public function impocrtBuilderCSV(Request $request) {
    $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
    $fileName     = $request->file('csv_file')->getClientOriginalName();
    $filePath     = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
    $fileFullPath = storage_path('app/public/' . $filePath);
    $file         = pathinfo($fileFullPath, PATHINFO_BASENAME);

    return response()->json(['message' => "CSV file {$file} imported successfully.", 'path' => $fileFullPath]);

    // $hna          = "C:\Users\Admin\Documents\app-rosetta\storage\app/public/csv_uploads/FluencyBuilderLessonDetailReport.2024-09-04-2024-12-25.csv";

  }

  // Perform batch insert
  public function handle(Request $request) {
    set_time_limit(300);

    // Validate the incoming request to ensure the CSV path is provided
    $request->validate(['csv_path' => 'required']);

    // Get the basename (filename with extension)
    $fileWithExtension = pathinfo($request->csv_path, PATHINFO_BASENAME);

    [$prefix, $dateRange]  = Str::of(pathinfo($fileWithExtension, PATHINFO_FILENAME))->explode('.');
    [$startDate, $endDate] = Str::of($dateRange)->explode('-')->chunk(3)->map(fn($chunk) => $chunk->implode('-'))->toArray();
    $this->fileName        = 'Builder_' . $endDate;
    // Check if the file has already been imported
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
      $groupedByLanguage = collect($data)->groupBy('langue')->map(function ($students, $language) use (&$coursesToInsert) {
        return $students->groupBy('email')->map(function ($userCourses, $email) use ($language, &$coursesToInsert) {

          // Retrieve the result IDs based on the email and language
          $resultIds = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('languages', 'results.language_id', '=', 'languages.id')
            ->where('users.email', strtolower($email))
            ->where('languages.libelle', strtolower($language))
            ->pluck('results.id');

          // Log and skip if no result IDs found
          if ($resultIds->isEmpty()) {
            Log::warning("No results found for email: {$email} and language: {$language}");

            return []; // Skip this user if no results found
          }

          // Fetch the user details directly from the users table
          $user = DB::table('users')->where('email', strtolower($email))->first();

          if (!$user) {
            Log::warning("User not found for email: {$email}");

            return [];
          }

          $firstName = $user->first_name;
          $lastName  = $user->last_name;
          $userCourses->groupBy('cours_name')->map(function ($lessons, $courseName) use ($resultIds, &$coursesToInsert, $firstName, $lastName, $email, $language, ) {
            $resultId = $resultIds->first(); // Use the first result ID (you can customize if needed)

            // Prepare the course data for insertion
            $coursesToInsert[] = [
              'result_id'      => $resultId,
              'cours_progress' => $lessons->pluck('cours_progress')->unique()->first() ?: 0, // Default to 0 if empty
              'cours_grade' => $lessons->pluck('cours_grade')->unique()->first() ?: 0, // Default to 0 if empty
              'cours_name' => $courseName,
              'total_lessons'  => $lessons->pluck('lesson_name')->unique()->count(),
              'file'           => strtolower($this->fileName),

            ];
          });

          return $userCourses;
        });
      });
      // dd($coursesToInsert);
      DB::transaction(function () use ($coursesToInsert) {
        $maxPlaceholders = 65535;
        $fieldsPerRow    = 30; // Adjust this to the actual number of fields you're inserting
        $maxRows         = floor($maxPlaceholders / $fieldsPerRow);
        $chunks          = array_chunk($coursesToInsert, $maxRows);
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

    $rows           = [];
    $desiredColumns = [3, 5, 8, 9, 11, 12]; // Specify the indices of the columns you want to extract (e.g., column 0 and column 2)

    if (($handle = fopen($path, 'r')) !== false) {
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        // Extract only the columns you need
        $filteredRow = array_intersect_key($data, array_flip($desiredColumns));
        $rows[]      = $filteredRow;
      }
      fclose($handle);
    }

    $header = array_shift($rows);
    // dd($rows);
    if (!$header) {
      Log::error('CSV file is empty or header is missing.');

      return [];
    }

    $indices = [
      $emailIndex = array_search('Email', $header),
      $LangueIndex = array_search('Language of Study', $header),

      $CoursNameIndex = array_search('Course Name', $header),
      $CoursProgressIndex = array_search('Course Progress', $header),
      $CoursGradeIndex = array_search('Course Grade', $header),
      $LessonNameIndex = array_search('Lesson Name', $header),
    ];

    $missingHeaders = array_filter($indices, fn($index) => $index === false);
    if (!empty($missingHeaders)) {
      Log::error('Missing required headers: ' . implode(', ', array_keys($missingHeaders)));

      return [];
    }

    $extractedData = [];
    foreach ($rows as $row) {
      $extractedData[] = [
        'email'          => isset($row[$emailIndex]) && Str::endsWith($row[$emailIndex], strtolower(env('DOMAIN_NAME'))) ? $row[$emailIndex] : null,
        'langue'         => isset($row[$LangueIndex]) && Str::contains($row[$LangueIndex], '(') ? trim(Str::before($row[$LangueIndex], '(')) : (isset($row[$LangueIndex]) ? trim($row[$LangueIndex]) : ''),
        'cours_name'     => isset($row[$CoursNameIndex]) ? $row[$CoursNameIndex] : null,
        'cours_progress' => isset($row[$CoursProgressIndex]) ? $row[$CoursProgressIndex] : null,
        'cours_grade'    => isset($row[$CoursGradeIndex]) ? $row[$CoursGradeIndex] : null,
        'lesson_name'    => isset($row[$LessonNameIndex]) ? $row[$LessonNameIndex] : null,

      ];
    }
    // dd($extractedData);

    return array_filter($extractedData, fn($data) => !empty($data['email']));
  }

  public function exportToExcel() {
    set_time_limit(400);
    // Load the locally stored data
    $filePath = storage_path('builder/builder.json');

    if (!file_exists($filePath)) {
      return response()->json(['message' => 'No data to export.'], 404);
    }

    $coursesToInsert = json_decode(file_get_contents($filePath), true);

    // Export the data to Excel (or CSV)

    return Excel::download(new CouresExport($coursesToInsert), 'builder .xlsx');
  }
  // public function exportCoursesToCsv() {
  //   set_time_limit(800);

  //   return Excel::download(new CouresExport, 'courses.csv', \Maatwebsite\Excel\Excel::CSV);
  // }

  public function exportCourseToCsv() {
    set_time_limit(400);
    $fileValue = 'builder_2024-12-28';
    $fileName  = 'courses_' . $fileValue . '.csv'; // Define the CSV file name
    $filePath  = storage_path('app/public/' . $fileName); // Define the file path
    $handle    = fopen($filePath, 'w'); // Open file for writing

    // Add UTF-8 BOM to ensure compatibility with Excel
    fwrite($handle, "\xEF\xBB\xBF");

    // Write the CSV header
    fputcsv($handle, [
      'File',
      'First Name',
      'Last Name',
      'Email',
      'Language',
      'Institution',
      'Branch',
      'Group',
      'Semester',
      'Course Name',
      'Course Progress',
      'Course Grade',
      'Total Lessons',
    ]);

    DB::table('results as r')
      ->join('languages as l', 'r.language_id', '=', 'l.id')
      ->join('students as s', 'r.student_id', '=', 's.id')
      ->join('users as u', 's.user_id', '=', 'u.id')
      ->join('institutions as i', 's.institution_id', '=', 'i.id')
      ->join('courses as c', 'c.result_id', '=', 'r.id')
      ->leftJoin('groups as g', 's.group_id', '=', 'g.id')
      ->leftJoin('branches as b', 's.branch_id', '=', 'b.id')
      ->leftJoin('semesters as sm', 's.semester_id', '=', 'sm.id')
      ->select([
        'c.file',
        'u.first_name',
        'u.last_name',
        'u.email as user_email',
        'l.libelle as language_libelle',
        'i.libelle as institution_libelle',
        'b.libelle as branch_libelle',
        'g.libelle as group_libelle',
        'sm.libelle as semester_libelle',
        'c.cours_name',
        'c.cours_progress',
        'c.cours_grade',
        'c.total_lessons',
      ])
      ->where('c.file', '=', strtolower($fileValue)) // Add the condition here
      ->orderByDesc('c.total_lessons')
      ->chunk(900000, function ($rows) use ($handle) { // Adjust chunk size for optimal performance
        foreach ($rows as $row) {
          // Write each record to the CSV file
          fputcsv($handle, [
            $row->file,
            $row->first_name,
            $row->last_name,
            $row->user_email,
            $row->language_libelle,
            $row->institution_libelle,
            $row->branch_libelle,
            $row->group_libelle,
            $row->semester_libelle,
            $row->cours_name,
            $row->cours_progress,
            $row->cours_grade,
            $row->total_lessons,
          ]);
        }
      });

    // Return the file as a downloadable response

    return response()->download($filePath)->deleteFileAfterSend(true);
  }

}
