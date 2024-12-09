<?php

namespace App\Jobs;

use App\Models\Language;
use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Str;

class ProcessCSVGrowthReportJob implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $filePath;

  /**
   * Create a new job instance.
   *
   * @param string $filePath
   */
  public function __construct($filePath) {
    $this->filePath = $filePath;
  }

  /**
   * Process the CSV file.
   */
  private function processCSV($path) {
    if (!file_exists($path)) {
      Log::error("File not found: {$path}");

      return [];
    }

    $rows = [];
    if (($handle = fopen($path, 'r')) !== false) {
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $rows[] = $data;
      }
      fclose($handle);
    }

    $header = array_shift($rows);
    if (!$header) {
      Log::error('CSV file is empty or header is missing.');

      return [];
    }

    $indices = [
      $nameIndex = array_search('Last Name', $header),
      $prenomIndex = array_search('First Name', $header),
      $emailIndex = array_search('Email', $header),
      $GroupIndex = array_search('Group(s)', $header),
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

    return array_filter($extractedData, fn($data) => !empty($data['email']));
  }

  /**
   * Process grouped language data.
   */
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

  /**
   * Execute the job.
   */
  public function handle() {
    try {
      Log::info("Job started for file: {$this->filePath}");

      $data = $this->processCSV($this->filePath);
      // dd($data[0]);
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
      $batchInsertData = [];
      foreach ($processedData as $dataa) {
        $result = DB::table('users')
          ->join('students', 'users.id', '=', 'students.user_id')
          ->select('students.id as student_id')
          ->where('users.email', trim(strtolower($dataa['email'])))
          ->first();

        $langueId = !empty($dataa['langue'])
        ? Language::updateOrCreate(['libelle' => strtolower($dataa['langue'])])->id
        : null;

        $batchInsertData[] = [
          'language_id'  => $langueId,
          'student_id'   => $result->student_id ?? null,
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
        ];

      }
      DB::transaction(function () use ($batchInsertData) {
        if (!empty($batchInsertData)) {
          $chunks = array_chunk($batchInsertData, 5000);
          foreach ($chunks as $chunk) {
            Result::insert($chunk);
          }
        }
      });

      Log::info('Data inserted successfully.');
    } catch (\Exception $e) {
      Log::error("Job failed with exception: {$e->getMessage()}", [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e; // Let Laravel mark the job as failed
    }
  }
}
