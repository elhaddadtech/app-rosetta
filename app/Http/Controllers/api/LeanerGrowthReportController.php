<?php

namespace App\Http\Controllers\api;

use App\Exports\LearnerGrowthExport;
use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class LeanerGrowthReportController extends Controller {
  public $fileName = '';

  public $csvFile   = null;
  public $learner   = [];
  public $usersNull = [];

  public function importGrowthCSV(Request $request) {
    // / Changer temporairement la taille maximale des fichiers téléchargés

    ini_set('post_max_size', '1024M'); // Définit la taille maximale des données POST
    ini_set('upload_max_filesize', '1024M'); // Définit la taille maximale des fichiers téléchargés

    $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
    $fileName = $request->file('csv_file')->getClientOriginalName();

    // dd(strtolower($fileName));
    $filePath     = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
    $fileFullPath = storage_path('app/public/' . $filePath);

    return response()->json(['message' => "CSV file {$this->csvFile} imported successfully.", 'path' => $fileFullPath]);

  }

  private function processCSV($path) {
    // if (!file_exists($path)) {

    //   Log::error("File not found: {$path}");

    //   return [];
    // }

    $rows           = [];
    $desiredColumns = [3, 5, 8, 10, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28]; // Specify the indices of the columns you want to extract (e.g., column 0 and column 2)

    if (($handle = fopen($path, 'r')) !== false) {
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {

        // Extract only the columns you need
        $filteredRow = array_intersect_key($data, array_flip($desiredColumns));
        $rows[]      = $filteredRow;

      }
      fclose($handle);
    }

    $header = array_shift($rows);

    if (!$header) {
      Log::error('CSV file is empty or header is missing.');

      return [];
    }

    $indices = [
      $emailIndex = array_search('Email', $header),
      $LangueIndex = array_search('Language of Study', $header),

      $TypeTest1Index = array_search('Test 1 Type', $header),
      $DateTest1Index = array_search('Test 1 Date', $header),
      $Score1Index = array_search('Test 1 Scaled Score', $header),
      $TestLevel1Index = array_search('Test 1 CEFR Level', $header),

      $DesktopTimeIndex = array_search('Desktop Learning Time (HH:MM:SS)', $header),
      $MobileTimeIndex = array_search('Mobile Time (HH:MM:SS)', $header),
      $ProductTimeIndex = array_search('Total Time Spent in Product (HH:MM:SS)', $header),
      $TotalTimeIndex = array_search('Test 1 Time Spent', $header),

      $TypeTest2Index = array_search('Test 2 Type', $header),
      $DateTest2Index = array_search('Test 2 Date', $header),
      $Score2Index = array_search('Test 2 Scaled Score', $header),
      $TestLevel2Index = array_search('Test 2 CEFR Level', $header),

      $TypeTest3Index = array_search('Test 3 Type', $header),
      $DateTest3Index = array_search('Test 3 Date', $header),
      $Score3Index = array_search('Test 3 Scaled Score', $header),
      $TestLevel3Index = array_search('Test 3 CEFR Level', $header),

      // $TypeTest4Index = array_search('Test 4 Type', $header),
      // $DateTest4Index = array_search('Test 4 Date', $header),
      // $Score4Index = array_search('Test 4 Scaled Score', $header),
      // $TestLevel4Index = array_search('Test 4 CEFR Level', $header),
    ];

    $missingHeaders = array_filter($indices, fn($index) => $index === false);
    if (!empty($missingHeaders)) {
      Log::error('Missing required headers: ' . implode(', ', array_keys($missingHeaders)));

      // return [];
    }

    // Test types
    $test1_type = 'Placement for Catalyst';
    $test2_type = 'Proficiency Test 1';
    $test3_type = 'Proficiency Test 2';
    $test4_type = 'Proficiency Test 3';

    $extractedData = [];

// Helper function to convert HH:MM:SS to seconds
    function timeToSeconds($time) {
      if (!$time) {
        return 0;
      }
      // Handle empty or null values
      $parts = explode(':', $time);
      if (count($parts) == 3) {
        return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
      }

      return 0;
    }

// Helper function to convert seconds back to HH:MM:SS format
    function secondsToTime($seconds) {
      return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
    }

// Extract relevant data from rows
    foreach ($rows as $row) {

      $email  = isset($row[$emailIndex]) && Str::endsWith($row[$emailIndex], strtolower(env('DOMAIN_NAME'))) ? $row[$emailIndex] : null;
      $langue = isset($row[$LangueIndex]) && Str::contains($row[$LangueIndex], '(')
      ? trim(Str::before($row[$LangueIndex], '('))
      : (isset($row[$LangueIndex]) ? trim($row[$LangueIndex]) : '');

      $data = [
        'email'        => $email,
        'langue'       => $langue,
        'desktop_time' => $row[$DesktopTimeIndex] ?? '00:00:00',
        'mobile_time'  => $row[$MobileTimeIndex] ?? '00:00:00',
        'test_Time'    => $row[$TotalTimeIndex] ?? '00:00:00',
        'total_time'   => $row[$ProductTimeIndex] ?? '00:00:00',
        'type_test_1'  => null,

        'date_test_1'  => null,
        'score_test_1' => null, 'level_test_1' => null,
        'type_test_2'  => null,

        'date_test_2'  => null,
        'score_test_2' => null, 'level_test_2' => null,

        'type_test_3'  => null,
        'date_test_3'  => null,
        'score_test_3' => null,
        'level_test_3' => null,
        'score_test_4' => null,
        'level_test_4' => null,
      ];

      // Check for Test 1 (Placement for Catalyst)
      if (isset($row[$TypeTest1Index]) && strtolower($row[$TypeTest1Index]) == strtolower($test1_type)) {
        $data['type_test_1']  = $row[$TypeTest1Index] ?? null;
        $data['date_test_1']  = $row[$DateTest1Index] ?? null;
        $data['score_test_1'] = $row[$Score1Index] ?? null;
        $data['level_test_1'] = $row[$TestLevel1Index] ?? null;
        $data['type_test_2']  = $row[$TypeTest2Index] ?? null;
        $data['date_test_2']  = $row[$DateTest2Index] ?? null;
        $data['score_test_2'] = $row[$Score2Index] ?? null;
        $data['level_test_2'] = $row[$TestLevel2Index] ?? null;
      }

      // Check for Test 2 (Proficiency Test 1)
      if (isset($row[$TypeTest1Index]) && strtolower($row[$TypeTest1Index]) == strtolower($test2_type)) {
        $data['type_test_2']  = $row[$TypeTest1Index] ?? null;
        $data['date_test_2']  = $row[$DateTest1Index] ?? null;
        $data['score_test_2'] = $row[$Score1Index] ?? null;
        $data['level_test_2'] = $row[$TestLevel1Index] ?? null;
      }
      if ($indices[14]) {
        // Check for Test 3 (Proficiency Test 2)
        if (isset($row[$TypeTest3Index]) && strtolower($row[$TypeTest3Index]) == strtolower($test3_type)) {
          $data['type_test_3']  = $row[$TypeTest3Index] ?? null;
          $data['date_test_3']  = $row[$DateTest3Index] ?? null;
          $data['score_test_3'] = $row[$Score3Index] ?? null;
          $data['level_test_3'] = $row[$TestLevel3Index] ?? null;
        }
        // Check for Test 3 (Proficiency Test 2)
        if (isset($row[$TypeTest2Index]) && strtolower($row[$TypeTest2Index]) == strtolower($test3_type)) {
          $data['type_test_3']  = $row[$TypeTest2Index] ?? null;
          $data['date_test_3']  = $row[$DateTest2Index] ?? null;
          $data['score_test_3'] = $row[$Score2Index] ?? null;
          $data['level_test_3'] = $row[$TestLevel2Index] ?? null;
        }
        if (
          isset($row[$TypeTest1Index]) && strtolower($row[$TypeTest1Index]) == strtolower($test2_type)
          && isset($row[$TypeTest2Index]) && strtolower($row[$TypeTest2Index]) == strtolower($test3_type)
          && isset($row[$TypeTest3Index]) && strtolower($row[$TypeTest3Index]) == strtolower($test4_type)
        ) {

          $data['type_test_1']  = $row[$TypeTest1Index] ?? null;
          $data['date_test_1']  = $row[$DateTest1Index] ?? null;
          $data['score_test_1'] = $row[$Score1Index] ?? null;
          $data['level_test_1'] = $row[$TestLevel1Index] ?? null;

          $data['type_test_2']  = $row[$TypeTest2Index] ?? null;
          $data['date_test_2']  = $row[$DateTest2Index] ?? null;
          $data['score_test_2'] = $row[$Score2Index] ?? null;
          $data['level_test_2'] = $row[$TestLevel2Index] ?? null;

          $data['type_test_3']  = $row[$TypeTest3Index] ?? null;
          $data['date_test_3']  = $row[$DateTest3Index] ?? null;
          $data['score_test_3'] = $row[$Score3Index] ?? null;
          $data['level_test_3'] = $row[$TestLevel3Index] ?? null;
        }
        if (
          isset($row[$TypeTest1Index]) && strtolower($row[$TypeTest1Index]) == strtolower($test2_type)
          && isset($row[$TypeTest2Index]) && strtolower($row[$TypeTest2Index]) == strtolower($test3_type)
          && isset($row[$TypeTest3Index]) && strtolower($row[$TypeTest3Index]) == strtolower($test3_type)
        ) {

          $data['type_test_1']  = $row[$TypeTest1Index] ?? null;
          $data['date_test_1']  = $row[$DateTest1Index] ?? null;
          $data['score_test_1'] = $row[$Score1Index] ?? null;
          $data['level_test_1'] = $row[$TestLevel1Index] ?? null;

          $data['type_test_2']  = $row[$TypeTest2Index] ?? null;
          $data['date_test_2']  = $row[$DateTest2Index] ?? null;
          $data['score_test_2'] = $row[$Score2Index] ?? null;
          $data['level_test_2'] = $row[$TestLevel2Index] ?? null;

          $data['type_test_3']  = $row[$TypeTest3Index] ?? null;
          $data['date_test_3']  = $row[$DateTest3Index] ?? null;
          $data['score_test_3'] = $row[$Score3Index] ?? null;
          $data['level_test_3'] = $row[$TestLevel3Index] ?? null;
        }

      }

      $extractedData[] = $data;
    }

// Define the required keys
    $requiredKeys = [
      'email', 'langue',
      'desktop_time', 'mobile_time', 'test_Time', 'total_time',
      'type_test_1', 'date_test_1', 'score_test_1', 'level_test_1',
      'type_test_2', 'date_test_2', 'score_test_2', 'level_test_2',
      'type_test_3', 'date_test_3', 'score_test_3', 'level_test_3',
      'score_test_4', 'level_test_4',
    ];

    $mergedData = [];

// Process each object in the dataset
    foreach ($extractedData as $item) {
      $key = $item['email'] . '|' . $item['langue'];

      // If this email-language combination doesn't exist, initialize it
      if (!isset($mergedData[$key])) {
        $mergedData[$key]                 = array_fill_keys($requiredKeys, null);
        $mergedData[$key]['email']        = $item['email'];
        $mergedData[$key]['langue']       = $item['langue'];
        $mergedData[$key]['desktop_time'] = 0;
        $mergedData[$key]['mobile_time']  = 0;
        $mergedData[$key]['total_time']   = 0;
      }

      // Merge test data, keeping the latest or most complete values
      foreach (['type_test_1', 'date_test_1', 'score_test_1', 'level_test_1',
        'type_test_2', 'date_test_2', 'score_test_2', 'level_test_2',
        'type_test_3', 'date_test_3', 'score_test_3', 'level_test_3', 'score_test_4', 'level_test_4'] as $testKey) {
        if (!empty($item[$testKey])) {
          $mergedData[$key][$testKey] = $item[$testKey];
        }

      }

      // Sum time values
      $mergedData[$key]['desktop_time'] += timeToSeconds($item['desktop_time'] ?? '00:00:00');
      $mergedData[$key]['mobile_time'] += timeToSeconds($item['mobile_time'] ?? '00:00:00');
      $mergedData[$key]['total_time'] += timeToSeconds($item['total_time'] ?? '00:00:00');
    }

// Convert seconds back to HH:MM:SS format
    foreach ($mergedData as &$data) {
      $data['desktop_time'] = secondsToTime($data['desktop_time']);
      $data['mobile_time']  = secondsToTime($data['mobile_time']);
      $data['total_time']   = secondsToTime($data['total_time']);
    }

//     }
    foreach ($mergedData as &$data) {
      // Extract scores correctly
      $score2 = isset($data['score_test_2']) ? explode('/', $data['score_test_2'])[0] : null;
      $score3 = isset($data['score_test_3']) ? explode('/', $data['score_test_3'])[0] : null;

      // Trim values and convert to integers
      $score2 = is_numeric(trim($score2)) ? (int) trim($score2) : null;
      $score3 = is_numeric(trim($score3)) ? (int) trim($score3) : null;

      // Determine max score
      if (!is_null($score2) && !is_null($score3)) {
        $data['score_test_4'] = max($score2, $score3) . '/400';
      } elseif (!is_null($score2) && is_null($score3)) {
        $data['score_test_4'] = $score2 . '/400';
      } else {
        $data['score_test_4'] = null;
      }

      // Extract levels
      $level2 = $data['level_test_2'] ?? null;
      $level3 = $data['level_test_3'] ?? null;

      // Ensure values are trimmed and processed correctly
      $level2 = trim($level2);
      $level3 = trim($level3);

      // Determine max level
      if (!is_null($level2) && !is_null($level3)) {
        $data['level_test_4'] = max($level2, $level3);
      } elseif (!is_null($level2) && is_null($level3)) {
        $data['level_test_4'] = $level2;
      } else {
        $data['level_test_4'] = null;
      }
    }

// Unset reference to avoid unintended modifications

    unset($data);
    $mergedData = array_values($mergedData);
    // $jsonData   = json_encode($mergedData, JSON_PRETTY_PRINT);

    return (array_filter($mergedData, fn($data) => !empty($data['email'])));
  }

  private function processLanguageData($records) {
    return collect($records->groupBy('email'))->map(function ($records, $email) {
      $totalSeconds = [
        'total'   => 0,
        'desktop' => 0,
        'mobile'  => 0,
        'test'    => 0,
      ];

      $finalScore = 0;
      $finalLevel = '';

      foreach ($records as $record) {
        $currentScore = $record['score_test_1'] ?? 0;
        if ($currentScore > $finalScore) {
          $finalScore = $currentScore;
          $finalLevel = $record['level_test_1'] ?? '';
        }

        foreach (['total_time' => 'total', 'desktop_time' => 'desktop', 'mobile_time' => 'mobile', 'test_Time' => 'test'] as $field => $key) {
          if (!empty($record[$field])) {
            [$hours, $minutes, $seconds] = array_map('intval', explode(':', $record[$field]) + [0, 0, 0]);
            $totalSeconds[$key] += ($hours * 3600) + ($minutes * 60) + $seconds;
          }
        }
      }

      $formattedTimes = collect($totalSeconds)->map(function ($seconds) {
        return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
      });
      // dd($formattedTimes);

      return [
        'email'        => $email,
        'langue'       => $record['langue'] ?? null,
        'type_test_1'  => $record['type_test_1'] ?? null,
        'date_test_1'  => $record['date_test_1'] ?? null,
        'score_test_1' => $finalScore,
        'level_test_1' => $finalLevel,
        'desktop_time' => $formattedTimes['desktop'],
        'mobile_time'  => $formattedTimes['mobile'],
        'test_Time'    => $formattedTimes['test'],
        'total_time'   => $formattedTimes['total'],
        'type_test_2'  => $record['type_test_2'] ?? null,
        'date_test_2'  => $record['date_test_2'] ?? null,
        'score_test_2' => $record['score_test_2'] ?? null,
        'level_test_2' => $record['level_test_2'] ?? null,
        'type_test_3'  => $record['type_test_3'] ?? null,
        'date_test_3'  => $record['date_test_3'] ?? null,
        'score_test_3' => $record['score_test_3'] ?? null,
        'level_test_3' => $record['level_test_3'] ?? null,
        // 'type_test_4'  => $record['type_test_4'] ?? null,
        // 'date_test_4'  => $record['date_test_4'] ?? null,
        'score_test_4' => $record['score_test_4'] ?? null,
        'level_test_4' => $record['level_test_4'] ?? null,
      ];
    });
  }

  public function handle(Request $request) {

    ini_set('max_execution_time', 300);
    $request->validate(['csv_path' => 'required']);
    $fileWithExtension = pathinfo($request->csv_path, PATHINFO_BASENAME);

    // return $fileWithExtension;
    [$prefix, $endDate] = Str::of(pathinfo($fileWithExtension, PATHINFO_FILENAME))->explode('.');

    $this->fileName = 'LearnerGrowth_' . $endDate;

    if (Result::where('file', strtolower($this->fileName))->exists()) {
      return response()->json(['message' => "CSV file {$fileWithExtension} already imported."]);

    }
    try {
      Log::info('Job started for file: {}');

      $data = $this->processCSV($request->csv_path);

      if (empty($data)) {
        Log::warning('No valid data found in the CSV file.');

        // return;
      }

      $groupedData = collect($data)->groupBy('langue');

      $processedData = LazyCollection::make(function () use ($groupedData) {
        foreach ($groupedData as $languageGroup) {
          foreach ($this->processLanguageData($languageGroup) as $processed) {
            yield $processed;
          }
        }
      });
      $batchInsertData = [];

      // Preload user-student mappings
      $userStudentMap = DB::table('users')
        ->join('students', 'users.id', '=', 'students.user_id')
        ->select('users.email', 'students.id as student_id')
        ->get()
        ->mapWithKeys(function ($item) {
          // dd($item->email);
          return [strtolower(trim($item->email)) => $item->student_id];
        });
      // Preload existing languages
      $existingLanguages = Language::pluck('id', 'libelle')->mapWithKeys(function ($value, $key) {
        return [strtolower($key) => $value];
      });
      $newLanguages = [];
      // Process data
      foreach ($processedData as $dataa) {
        $email = strtolower(trim($dataa['email']));

        $studentId = $userStudentMap[$email] ?? null;
        // dd($email);
        if ($studentId == null) {
          $this->usersNull[] = $email;
          Log::warning("Student ID not found for email: $email");
          continue;
        }
        $langue = strtolower($dataa['langue']);
        if ($langue && !isset($existingLanguages[$langue])) {
          $newLanguages[$langue] = null; // Mark for insertion
        }

        $batchInsertData[] = [
          // 'email'        => $email,
          // 'langue'       => $langue,
          'language_id'  => $langue && isset($existingLanguages[$langue]) ? $existingLanguages[$langue] : null,
          'student_id'   => $studentId,
          'type_test_1'  => $dataa['type_test_1'],
          'date_test_1'  => $dataa['date_test_1'],
          'score_test_1' => $dataa['score_test_1'],
          'level_test_1' => $dataa['level_test_1'],
          'desktop_time' => $dataa['desktop_time'],
          'mobile_time'  => $dataa['mobile_time'],
          'test_Time_1'  => $dataa['test_Time'],
          'total_time'   => $dataa['total_time'],
          'type_test_2'  => $dataa['type_test_2'],
          'date_test_2'  => $dataa['date_test_2'],
          'score_test_2' => $dataa['score_test_2'],
          'level_test_2' => $dataa['level_test_2'],
          'type_test_3'  => $dataa['type_test_3'],
          'date_test_3'  => $dataa['date_test_3'],
          'score_test_3' => $dataa['score_test_3'],
          'level_test_3' => $dataa['level_test_3'],

          'score_test_4' => $dataa['score_test_4'],
          'level_test_4' => $dataa['level_test_4'],
          'file'         => strtolower($this->fileName),
        ];
      }

      // Insert new languages (if any)
      if (!empty($newLanguages)) {
        foreach ($newLanguages as $langue => $id) {
          $id                         = Language::create(['libelle' => strtolower($langue)])->id;
          $existingLanguages[$langue] = $id;
        }

        // Update batch data with new language IDs
        foreach ($batchInsertData as &$data) {
          $langue              = strtolower($processedData[$data['email']]['langue']);
          $data['language_id'] = $existingLanguages[$langue] ?? null;
        }
      }

      // /// Convert extracted data to JSON format
      // $jsonData = json_encode($batchInsertData, JSON_PRETTY_PRINT);

      // // Define the file path
      // $filePath = storage_path('app/extracted_data1.json'); // Store inside Laravel storage/app folder

      // // Store the JSON data into the file
      // file_put_contents($filePath, $jsonData);
      // dd('success');

      // Perform batch insert
      DB::transaction(function () use ($batchInsertData) {
        $maxPlaceholders = 65535;
        $fieldsPerRow    = 22; // Adjust this to the actual number of fields you're inserting
        $maxRows         = floor($maxPlaceholders / $fieldsPerRow);
        $chunks          = array_chunk($batchInsertData, $maxRows);
        $this->learner[] = $chunks;

        if (count($this->usersNull) == 0) {
          foreach ($chunks as $chunk) {
            // Insert chunk if no file name condition is true
            Result::insert($chunk);
          }

        }

      });

      if (count($this->usersNull) > 0) {
        $students = collect($this->usersNull)->unique()->values()->toArray();
        // $nbStudents = collect($this->usersNull)->unique()->count();

        return response()->json([
          'message'     => 'some students do not exist in our database',
          'nb_students' => collect($this->usersNull)->unique()->count(),
          'students'    => $students,
        ]);
      };

      return response()->json(['message' => "CSVw file {$fileWithExtension} imported successfully."]);
    } catch (\Exception $e) {
      Log::error("Job failed with exception: {$e->getMessage()}", [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e; // Let Laravel mark the job as failed
    }
  }

  public function ExportDataLearnerGrowth() {
    return Excel::download(new LearnerGrowthExport, 'LearnerGrowthReport.xlsx');
  }

  public function exportResultsToCsv() {
    ini_set('max_execution_time', 300);
    $fileName = 'LearnerGrowth_results' . '.csv'; // Generate file name with timestamp
    $filePath = storage_path('app/public/' . $fileName); // Define file path
    $handle   = fopen($filePath, 'w'); // Open file for writing

    // Add BOM for UTF-8 encoding
    fwrite($handle, "\xEF\xBB\xBF");

    // Write the CSV header
    fputcsv($handle, [
      'File',
      'Last Name',
      'First Name',
      'Email',
      'Language',
      'Institution',
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
    ]);

    // Query and process data in chunks
    $isDataAvailable = false; // Flag to check if any data exists

    DB::table('results')
      ->join('languages', 'results.language_id', '=', 'languages.id')
      ->join('students', 'results.student_id', '=', 'students.id')
      ->join('users', 'students.user_id', '=', 'users.id')
      ->join('institutions', 'students.institution_id', '=', 'institutions.id')
      ->select([
        'results.file',
        'users.last_name',
        'users.first_name',
        'users.email as user_email',
        'languages.libelle as language_libelle',
        'institutions.libelle as institution_libelle',
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
      ->orderBy('results.file')
      ->chunk(900000, function ($results) use ($handle, &$isDataAvailable) {
        // Process each chunk of data
        foreach ($results as $result) {
          $isDataAvailable = true; // Set flag to true when data is found
          fputcsv($handle, [
            $result->file,
            $result->last_name,
            $result->first_name,
            $result->user_email,
            $result->language_libelle,
            $result->institution_libelle,
            $result->level_test_1,
            $result->score_test_1,
            $result->type_test_1,
            $result->date_test_1,
            $result->test_time_1,
            $result->desktop_time,
            $result->mobile_time,
            $result->total_time,
            $result->type_test_2,
            $result->date_test_2,
            $result->score_test_2,
            $result->level_test_2,
            $result->type_test_3,
            $result->date_test_3,
            $result->score_test_3,
            $result->level_test_3,
          ]);
        }
      });

    fclose($handle); // Close the file after writing

    // If no data was written, discard the file and return a response
    if (!$isDataAvailable) {
      unlink($filePath); // Delete the empty file

      return response()->json(['message' => 'No results found to export'], 404);
    }

    // Return the file as a downloadable response and delete it after sending

    return response()->download($filePath)->deleteFileAfterSend(true);
  }

}
