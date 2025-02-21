<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CefrMapping;
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
    set_time_limit(400);

    // Validate CSV file path
    $request->validate(['csv_path' => 'required']);

    // Extract filename
    $this->fileName = $this->extractFileName($request->csv_path);
    //  Check if this file has already been processed
    if (Course::where('file', $this->fileName)->exists()) {
      Log::warning("CSV file '{$this->fileName}' has already been processed.");

      return response()->json(['status' => false, 'message' => "CSV file '{$this->fileName}' has already been imported."], 409);
    }

    try {
      Log::info("Processing CSV file: {$request->csv_path}");

      // Process CSV Data
      $data = $this->processCSV($request->csv_path);

      if (empty($data)) {
        Log::warning('No valid data found in the CSV file.');

        return response()->json(['message' => 'No valid data found in the CSV file.'], 404);
      }

      // Process students and prepare course data
      $coursesToInsert = $this->processStudents($data);

      return response()->json([
        'status'  => true,
        'message' => 'Data processed and stored successfully.',
      ]);
    } catch (\Exception $e) {
      Log::error("Job failed: {$e->getMessage()}");

      return response()->json(['message' => 'Job failed', 'error' => $e->getMessage()], 500);
    }
  }

/**
 * Extracts the filename from the file path.
 */
  private function extractFileName($filePath) {
    $fileWithExtension     = pathinfo($filePath, PATHINFO_BASENAME);
    [$prefix, $dateRange]  = explode('.', pathinfo($fileWithExtension, PATHINFO_FILENAME));
    [$startDate, $endDate] = explode('-', $dateRange);

    return 'Foundations_' . $startDate . '_' . $dateRange;
  }

/**
 * Process students and calculate NoteCC dynamically.
 */
  private function processStudents($data) {

    $coursesToInsert = [];
    collect($data)->groupBy('langue')->each(function ($students, $language) use (&$coursesToInsert) {
      $students->groupBy('email')->each(function ($userCourses, $email) use ($language, &$coursesToInsert) {

        $studentData = $this->fetchStudentData($email, $language);

        if (!$studentData) {
          return;
        }

        $totalNoteFinal = 0;

        // Process each course
        $userCourses->groupBy('cours_name')->each(function ($lessons, $courseName) use (&$totalNoteFinal) {
          $totalNoteFinal += $this->calculateTotalNoteForCourse($lessons);
        });

        // Calculate scores dynamically
        $noteCC1 = $this->calculateNoteCC1($totalNoteFinal, $studentData['totalLessons']);
        $noteCC2 = $this->calculateNoteCC2($studentData['totalHours'], $studentData['studentCEFR'], $studentData['language']);
        $noteCC  = $this->calculateNoteCC($noteCC1, $noteCC2, $studentData['studentCEFR'], $studentData['language']);

        // Insert course data
        $this->insertStudentCourses($userCourses, $studentData, $noteCC1, $noteCC2, $noteCC, $coursesToInsert);
      });
    });

    return true;

  }

/**
 * Fetch student details, CEFR level, and total hours.
 */
  private function fetchStudentData($email, $language) {

    $result = DB::table('results')
      ->join('students', 'results.student_id', '=', 'students.id')
      ->join('users', 'students.user_id', '=', 'users.id')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->where('users.email', strtolower($email))
      ->where('languages.libelle', strtolower($language))
      ->select('results.id', 'results.level_test_1', 'results.total_time')
      ->first();

    if (!$result) {
      return null;
    }

    $cefrMapping = CefrMapping::where('level', strtolower($result->level_test_1))->where('language', strtolower($language))->first();

    if (!$cefrMapping) {
      return null;
    }

    return [
      'email'        => $email,
      'language'     => strtolower($language),
      'resultId'     => $result->id,
      'studentCEFR'  => strtolower($result->level_test_1),
      'totalLessons' => $cefrMapping->lesson,
      'totalHours'   => $this->convertTimeToHours($result->total_time),
    ];

  }

/**
 * Calculate NoteCC dynamically based on database-stored weights.
 */
  private function calculateNoteCC($noteCC1, $noteCC2, $studentCEFR, $language) {
    $weights = CefrMapping::where('level', $studentCEFR)
      ->where('language', $language)
      ->select('noteCC1_ratio', 'noteCC2_ratio')
      ->first();

    $noteCC1_weight = ($weights->noteCC1_ratio ?? 60) / 100;
    $noteCC2_weight = ($weights->noteCC2_ratio ?? 40) / 100;

    return max($noteCC1, ($noteCC1 * $noteCC1_weight) + ($noteCC2 * $noteCC2_weight));
  }

/**
 * Calculate NoteCC1.
 */
  private function calculateNoteCC1($totalNoteFinal, $totalLessons) {
    return ($totalLessons > 0) ? min(($totalNoteFinal / $totalLessons) * 20, 19.95) : 0;
  }

/**
 * Calculate NoteCC2.
 */
  private function calculateNoteCC2($totalHours, $studentCEFR, $language) {
    $seuil_heures_jours = CefrMapping::where('level', $studentCEFR)
      ->where('language', $language)
      ->value('seuil_heures_jours') ?? 20;

    return min(($totalHours / max($seuil_heures_jours, 1e-9)) * 20, 20);
  }

/**
 * Calculate the total note for a course based on lesson progress and grade.
 */
  private function calculateTotalNoteForCourse($lessons) {

    return $lessons->sum(function ($lesson) {
      $lessonProgress = floatval(rtrim($lesson['cours_progress'] ?? '0%', '%'));
      $lessonGrade    = floatval(rtrim($lesson['cours_grade'] ?? '0%', '%'));

      return ((($lessonProgress / 100) * ($lessonGrade / 100) * 4));
    });
  }

/**
 * Convert time string HH:MM:SS to hours.
 */
  private function convertTimeToHours($timeString) {
    if (!$timeString) {
      return 0;
    }

    list($hours, $minutes, $seconds) = explode(':', $timeString);

    return floatval($hours) + (floatval($minutes) / 60) + (floatval($seconds) / 3600);
  }

/**
 * Insert student courses into `$coursesToInsert`.
 */
  private function insertStudentCourses($userCourses, $studentData, $noteCC1, $noteCC2, $noteCC) {
    // s Disable Query Logging for Large Inserts (Prevents Memory Overload)
    DB::disableQueryLog();

    $coursesToInsert = [];

    foreach ($userCourses->groupBy('cours_name') as $courseName => $lessons) {
      $coursesToInsert[] = [
        // 'email'          => $studentData['email'],
        // 'language'       => $studentData['language'],
        'result_id'      => $studentData['resultId'],
        'cours_name'     => $courseName,
        'cours_progress' => $lessons->pluck('cours_progress')->unique()->first() ?: 0,
        'cours_grade'    => $lessons->pluck('cours_grade')->unique()->first() ?: 0,
        'total_lessons'  => $lessons->pluck('lesson_name')->unique()->count(),
        'noteCC1'        => round($noteCC1, 2),
        'noteCC2'        => round($noteCC2, 2),
        'noteCC'         => round($noteCC, 2),
        'file'           => strtolower($this->fileName),
        // 'created_at'     => now(),
        // 'updated_at'     => now(),
      ];
    }

    // ✅ **Only Proceed if Data Exists**
    if (!empty($coursesToInsert)) {
      $maxPlaceholders = 65535; // MySQL Limit
      $columnsPerRow   = count($coursesToInsert[0]); // Count columns in one row

      // ✅ Calculate Dynamic Chunk Size (Avoid MySQL Overload)
      $chunkSize = min(5000, floor($maxPlaceholders / $columnsPerRow)); // Safe Limit

      // ✅ **Batch Insert in Chunks**
      foreach (array_chunk($coursesToInsert, $chunkSize) as $chunk) {
        DB::table('courses')->insert($chunk);
      }
    }

  }

  private function processCSV($path) {
    if (!file_exists($path)) {
      Log::error("File not found: {$path}");

      return [];
    }

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
        'email'          => isset($row[$emailIndex]) && Str::endsWith($row[$emailIndex], strtolower(env('DOMAIN_NAME'))) ? $row[$emailIndex] : null,
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
