<?php

namespace App\Http\Controllers\api;

use App\Exports\CouresExport;
use App\Http\Controllers\Controller;
use App\Models\CefrMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class BuilderController extends Controller {
  public $fileName  = '';
  public $dataExcel = [];
  public function importBuilderCSV(Request $request) {
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
    // Validate CSV file path
    $request->validate(['csv_path' => 'required']);

    // Extract filename
    $this->fileName = $this->extractFileName($request->csv_path);

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
        'message'         => 'Data processed and stored successfully.',
        'coursesToInsert' => $coursesToInsert,
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

    return 'Builder_' . $endDate;
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

    return $coursesToInsert;
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

    $cefrMapping = CefrMapping::where('level', $result->level_test_1)->where('language', $language)->first();
    if (!$cefrMapping) {
      return null;
    }

    return [
      'email'        => $email,
      'language'     => $language,
      'resultId'     => $result->id,
      'studentCEFR'  => $result->level_test_1,
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

    return min(($totalHours / max($seuil_heures_jours, 1e-9)) * 20, 19.5);
  }

/**
 * Calculate the total note for a course based on lesson progress and grade.
 */
  private function calculateTotalNoteForCourse($lessons) {
    return $lessons->sum(function ($lesson) {
      $lessonProgress = floatval(rtrim($lesson['lesson_progress'] ?? '0%', '%'));
      $lessonGrade    = floatval(rtrim($lesson['lesson_grade'] ?? '0%', '%'));

      return (($lessonProgress / 100) * ($lessonGrade / 100));
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
  private function insertStudentCourses($userCourses, $studentData, $noteCC1, $noteCC2, $noteCC, &$coursesToInsert) {
    $userCourses->groupBy('cours_name')->each(function ($lessons, $courseName) use (&$coursesToInsert, $studentData, $noteCC1, $noteCC2, $noteCC) {
      $coursesToInsert[] = [
        'email'      => $studentData['email'],
        'language'   => $studentData['language'],
        'result_id'  => $studentData['resultId'],
        'cours_name' => $courseName,
        'noteCC1'    => round($noteCC1, 2),
        'noteCC2'    => round($noteCC2, 2),
        'noteCC'     => round($noteCC, 2),
        'file'       => strtolower($this->fileName),
      ];
    });
  }
/**
 * Reads a CSV file and extracts structured data.
 */
  private function processCSV($path) {
    if (!file_exists($path)) {
      Log::error("File not found: {$path}");

      return [];
    }

    $rows           = [];
    $desiredColumns = [3, 5, 8, 9, 11, 12, 13, 15]; // Columns to extract

    if (($handle = fopen($path, 'r')) !== false) {
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $filteredRow = array_intersect_key($data, array_flip($desiredColumns));
        $rows[]      = $filteredRow;
      }
      fclose($handle);
    }

    if (empty($rows)) {
      Log::error('CSV file is empty.');

      return [];
    }

    $header = array_shift($rows);
    if (!$header) {
      Log::error('CSV header missing.');

      return [];
    }

    $indices = [
      'emailIndex'          => array_search('Email', $header),
      'LangueIndex'         => array_search('Language of Study', $header),
      'CoursNameIndex'      => array_search('Course Name', $header),
      'CoursProgressIndex'  => array_search('Course Progress', $header),
      'CoursGradeIndex'     => array_search('Course Grade', $header),
      'LessonNameIndex'     => array_search('Lesson Name', $header),
      'LessonProgressIndex' => array_search('Lesson Progress', $header),
      'LessonGradeIndex'    => array_search('Lesson Grade', $header),
    ];

    if (in_array(false, $indices, true)) {
      Log::error('Missing required CSV headers.');

      return [];
    }

    $resultExtact = array_filter(array_map(function ($row) use ($indices) {
      $email  = $row[$indices['emailIndex']] ?? null;
      $langue = isset($row[$indices['LangueIndex']])
      ? trim(Str::before($row[$indices['LangueIndex']], '('))
      : ($row[$indices['LangueIndex']] ?? '');

      $coursGrade     = floatval(rtrim($row[$indices['CoursGradeIndex']] ?? '0', '%'));
      $lessonGrade    = floatval(rtrim($row[$indices['LessonGradeIndex']] ?? '0', '%'));
      $lessonProgress = floatval(rtrim($row[$indices['LessonProgressIndex']] ?? '0', '%'));

      $noteLesson = (($lessonProgress / 100) * ($lessonGrade / 100));

      return [
        'email'           => $email && Str::endsWith($email, strtolower(env('DOMAIN_NAME'))) ? $email : null,
        'langue'          => $langue,
        'cours_name'      => $row[$indices['CoursNameIndex']] ?? null,
        'cours_progress'  => $row[$indices['CoursProgressIndex']] ?? null,
        'cours_grade'     => $coursGrade . '%',
        'lesson_name'     => $row[$indices['LessonNameIndex']] ?? null,
        'lesson_progress' => $lessonProgress . '%',
        'lesson_grade'    => $lessonGrade . '%',
        'note_lesson'     => $noteLesson,
      ];
    }, $rows), fn($data) => !empty($data['email']));

    return ($resultExtact);
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
    set_time_limit(600);
    $fileValue = 'builder_2025-01-05';
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
      ->chunk(800, function ($rows) use ($handle) { // Adjust chunk size for optimal performance
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
