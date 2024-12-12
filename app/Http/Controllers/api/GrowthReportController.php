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

class GrowthReportController extends Controller {
  public $fileName = false;
  public $csvFile  = null;
  public $learner = [];

  // public function uploadCSV(Request $request) {
  //   // 1. Validate the uploaded file
  //   $request->validate([
  //     'csv_file' => 'required|file|mimes:csv,txt',
  //   ]);

  //   // 2. Store the file
  //   $fileName = $request->file('csv_file')->getClientOriginalName();
  //   $filePath = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
  //   // $fileFullPath = storage_path('app/public/uploads/' . $fileName);
  //   $fileFullPath = storage_path('app/public/' . $filePath);
  //   ProcessCSVGrowthReportJob::dispatch($fileFullPath);
  //   // exec('php artisan queue:work');

  //   // 4. Respond to the user

  //   return response()->json(['message' => 'CSV file uploaded successfully. Data is being processed.']);
  // }





    //LearnerGrowth methodes [import,processCSV,processLanguageData,handle]
  public function import(Request $request) {
    $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
    $fileName       = $request->file('csv_file')->getClientOriginalName();
    $this->csvFile = strtolower($fileName);
    $this->fileName = Result::where('file', strtolower($fileName))->exists();
    // dd(strtolower($fileName));
    $filePath     = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
    $fileFullPath = storage_path('app/public/' . $filePath);
    $handleLearnerGrowth = $this->handle($fileFullPath) ;

    if($handleLearnerGrowth == null) return response()->json(['message' => "CSV file {$this->csvFile} already imported"]);
    return response()->json(['message' => 'CSV file {$this->csvFile} imported successfully.']);

  }

  private function processCSV($path) {
    if (!file_exists($path)) {
      Log::error("File not found: {$path}");

      return [];
    }

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
    ];

    $missingHeaders = array_filter($indices, fn($index) => $index === false);
    if (!empty($missingHeaders)) {
      Log::error('Missing required headers: ' . implode(', ', array_keys($missingHeaders)));

      return [];
    }

    $extractedData = [];
    foreach ($rows as $row) {
      $extractedData[] = [
        'email'        => isset($row[$emailIndex]) ? $row[$emailIndex] : null,
        'langue'       => isset($row[$LangueIndex]) && Str::contains($row[$LangueIndex], '(') ? trim(Str::before($row[$LangueIndex], '(')) : (isset($row[$LangueIndex]) ? trim($row[$LangueIndex]) : ''),
        'type_test_1'  => isset($row[$TypeTest1Index]) ? $row[$TypeTest1Index] : null,
        'date_test_1'  => isset($row[$DateTest1Index]) ? $row[$DateTest1Index] : null,
        'score_test_1' => isset($row[$Score1Index]) ? $row[$Score1Index] : null,
        'level_test_1' => isset($row[$TestLevel1Index]) ? $row[$TestLevel1Index] : null,
        'desktop_time' => isset($row[$DesktopTimeIndex]) ? $row[$DesktopTimeIndex] : null,
        'mobile_time'  => isset($row[$MobileTimeIndex]) ? $row[$MobileTimeIndex] : null,
        'test_Time'    => isset($row[$TotalTimeIndex]) ? $row[$TotalTimeIndex] : null,
        'total_time'   => isset($row[$ProductTimeIndex]) ? $row[$ProductTimeIndex] : null,
        'type_test_2'  => isset($row[$TypeTest2Index]) ? $row[$TypeTest2Index] : null,
        'date_test_2'  => isset($row[$DateTest2Index]) ? $row[$DateTest2Index] : null,
        'score_test_2' => isset($row[$Score2Index]) ? $row[$Score2Index] : null,
        'level_test_2' => isset($row[$TestLevel2Index]) ? $row[$TestLevel2Index] : null,
        'type_test_3'  => isset($row[$TypeTest3Index]) ? $row[$TypeTest3Index] : null,
        'date_test_3'  => isset($row[$DateTest3Index]) ? $row[$DateTest3Index] : null,
        'level_test_3' => isset($row[$TestLevel3Index]) ? $row[$TestLevel3Index] : null,
      ];
    }
    // dd($header);

    return array_filter($extractedData, fn($data) => !empty($data['email']));
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
        $currentScore = explode('/', $record['score_test_1'])[0] ?? 0;
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
        'score_test_1' => "{$finalScore}/400",
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
      ];
    });
  }

  public function handle($filePath) {
    // ini_set('max_execution_time', 100);
    try {
      Log::info("Job started for file: {$filePath}");
      // dd($filePath);
      $data = $this->processCSV($filePath);
      if (empty($data)) {
        Log::warning('No valid data found in the CSV file.');

        return;
      }

      $groupedData = collect($data)->groupBy('langue');
      // dd($groupedData);
      $processedData = LazyCollection::make(function () use ($groupedData) {
        foreach ($groupedData as $languageGroup) {
          foreach ($this->processLanguageData($languageGroup) as $processed) {
            yield $processed;
          }
        }
      });
      // dd($processedData->first());
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
        $email     = strtolower(trim($dataa['email']));
        $studentId = $userStudentMap[$email] ?? null;

        $langue = strtolower($dataa['langue']);
        if ($langue && !isset($existingLanguages[$langue])) {
          $newLanguages[$langue] = null; // Mark for insertion
        }

        $batchInsertData[] = [
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
          'file'         => $this->csvFile ,
        ];
      }

      // Insert new languages (if any)
      if (!empty($newLanguages)) {
        foreach ($newLanguages as $langue => $id) {
          $id                         = Language::create(['libelle' => $langue])->id;
          $existingLanguages[$langue] = $id;
        }

        // Update batch data with new language IDs
        foreach ($batchInsertData as &$data) {
          $langue              = strtolower($processedData[$data['email']]['langue']);
          $data['language_id'] = $existingLanguages[$langue] ?? null;
        }
      }

      // Perform batch insert
      DB::transaction(function () use ($batchInsertData) {
        $maxPlaceholders = 65535;
        $fieldsPerRow    = 22; // Adjust this to the actual number of fields you're inserting
        $maxRows         = floor($maxPlaceholders / $fieldsPerRow);
        $chunks          = array_chunk($batchInsertData, $maxRows);
        $this->learner[] = $chunks;
        file_put_contents(storage_path('learnerGrowth/failed_chunks.json'), json_encode($chunks));
        // return Excel::download(new LearnerGrowthExport($chunks), "LearnerGrowthExcel.xlsx");
        if ($this->fileName) {
          return ;
        } else {
          foreach ($chunks as $chunk) {
            // Insert chunk if no file name condition is true
            Result::insert($chunk);
          }
        }
      });

      // dd('Successfully inserted');

      Log::info('Data inserted successfully.1');
    } catch (\Exception $e) {
      Log::error("Job failed with exception: {$e->getMessage()}", [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e; // Let Laravel mark the job as failed
    }
  }


  public function ExportDataLearnerGrowth(){
    return Excel::download(new LearnerGrowthExport, 'LearnerGrowthReport.xlsx');

  }
}
