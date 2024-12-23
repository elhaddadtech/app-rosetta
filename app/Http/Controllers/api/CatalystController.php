<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Imports\CatalystImport;
use App\Models\Language;
use App\Models\Result;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CatalystController extends Controller {
  public function importCatalyst(Request $request) {
    // Validate file input
    // set_time_limit(120);
    $request->validate([
      'csv_file' => 'required|mimes:csv,txt',
    ]);
    try {
      // Import file
      $import = new CatalystImport();
      dd(Excel::import($import, $request->file('csv_file')));
      Excel::import($import, $request->file('csv_file'));
      dd('ok');

      // Get processed data (assuming CatalystImport saves results somewhere accessible)
      $processedData = session('students') ?? [];

      return response()->json([
        'message' => 'Fichier traité avec succès',
        'data'    => $processedData,
      ]);

    } catch (\Exception $e) {
      // Handle exceptions
      return response()->json([
        'message' => 'Erreur lors de l\'importation du fichier: ' . $e->getMessage(),
      ], 500);
    }

  }

  public function import(Request $request) {
    // ini_set('memory_limit', '1G');
    // $request->validate([
    //   'csv_file' => 'required|file|mimes:csv,txt',
    // ]);
    $fileName        = $request->file('csv_file')->getClientOriginalName();
    $filePath        = $request->file('csv_file')->storeAs('uploads', $fileName, 'public');
    $fileFullPath    = storage_path('app/public/uploads/' . $fileName);
    $data            = $this->processCSV($fileFullPath);
    $filter_language = $this->filterLanguage($data);

    // $import = new CatalystAImport();

    // Excel::import($import, $fileFullPath);

    // $emails = $import->getEmails();
    // dd('3mail');
    // $ok = new CatalystCUImport();

    return response()->json(['data' => $filter_language], 200);
  }


  private function processCSV($path) {
    $rows = [];
    if (($handle = fopen($path, 'r')) !== false) {
      while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $rows[] = $data; // Collect each row
      }
      fclose($handle);
    }

    // Extract the header (first row) and the remaining rows
    $header = array_shift($rows);

    // Example: Ensure the header contains `name` and `email`$nameIndex === false ||
    $nameIndex   = array_search('Last Name', $header);
    $prenomIndex = array_search('First Name', $header);
    $emailIndex  = array_search('Email', $header);
    $GroupIndex  = array_search('Group(s)', $header);
    $LangueIndex = array_search('Language of Study', $header);

    $TypeTest1Index  = array_search('Test 1 Type', $header);
    $DateTest1Index  = array_search('Test 1 Date', $header);
    $Score1Index     = array_search('Test 1 Scaled Score', $header);
    $TestLevel1Index = array_search('Test 1 CEFR Level', $header);

    $DesktopTimeIndex = array_search('Desktop Learning Time (HH:MM:SS)', $header);
    $MobileTimeIndex  = array_search('Mobile Time (HH:MM:SS)', $header);
    $ProductTimeIndex = array_search('Total Time Spent in Product (HH:MM:SS)', $header);
    $TotalTimeIndex   = array_search('Test 1 Time Spent', $header);

    $TypeTest2Index  = array_search('Test 2 Type', $header);
    $DateTest2Index  = array_search('Test 2 Date', $header);
    $Score2Index     = array_search('Test 2 Scaled Score', $header);
    $TestLevel2Index = array_search('Test 2 CEFR Level', $header);

    $TypeTest3Index  = array_search('Test 3 Type', $header);
    $DateTest3Index  = array_search('Test 3 Date', $header);
    $Score3Index     = array_search('Test 3 Scaled Score', $header);
    $TestLevel3Index = array_search('Test 3 CEFR Level', $header);

    if ($emailIndex === false) {
      return [
        'error' => 'CSV file must contain "name" and "email" columns.',
      ];
    }

    // Extract name and email from each row
    $extractedData = [];
    foreach ($rows as $row) {
      $extractedData[] = [
        'full_name'    => trim((isset($row[$nameIndex]) ? $row[$nameIndex] : '') . ' ' . (isset($row[$prenomIndex]) ? $row[$prenomIndex] : '')),
        'email'        => isset($row[$emailIndex]) ? $row[$emailIndex] : null,
        'group'        => isset($row[$GroupIndex]) ? $row[$GroupIndex] : null,
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
    // Remove entries where all fields are empty
    $filteredData = array_filter($extractedData, function ($entry) {
      return !empty(array_filter($entry, fn($value) => !is_null($value) && $value !== ''));
    });

    return array_values($filteredData);
  }

  private function processLanguageData($recordsByEmail) {
    // dd($recordsByEmail[0]);

    return collect($recordsByEmail->groupBy('email'))->map(function ($records, $email) {
      $totalSeconds = [
        'total'   => 0,
        'desktop' => 0,
        'mobile'  => 0,
        'test'    => 0,
      ];

      $finalScore = 0;
      $finalLevel = '';

      foreach ($records as $record) {
        $currentScore = explode('/', $record['score_test_1'])[0];
        if ($currentScore > $finalScore) {
          $finalScore = $currentScore;
          $finalLevel = $record['level_test_1'];
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

      return [
        'full_name'    => $record['full_name'] ?? null,
        'email'        => $email,
        'group'        => $record['group'] ?? null,
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

  private function filterLanguage($data) {
    $groupedData = collect($data)->groupBy('langue');

    $processedData = LazyCollection::make(function () use ($groupedData) {
      foreach ($groupedData as $languageGroup) {
        foreach ($this->processLanguageData($languageGroup) as $processed) {
          yield $processed;
        }
      }
    });
    // dd($processedData);

    $batchInsertData = [];

    foreach ($processedData as $data) {
      if ($data['email'] == 'k.lamfichekh6986@uca.ac.ma' && $data['langue'] == 'English') {
        dd($data);
      }

      // dd($data['langue']);
      $result = DB::table('users')
        ->join('students', 'users.id', '=', 'students.user_id')
        ->select('students.id as student_id')
        ->where('users.email', trim(strtolower($data['email'])))
        ->first();

      $langueId = !empty($data['langue'])
      ? Language::updateOrCreate(['libelle' => strtolower($data['langue'])])->id
      : null;
      // dd($data);
      $batchInsertData[] = [
        'language_id'  => $langueId,
        'student_id'   => $result->student_id ?? null,
        'type_test_1'  => $data['type_test_1'],
        'date_test_1'  => $data['date_test_1'],
        'score_test_1' => $data['score_test_1'],
        'level_test_1' => $data['level_test_1'],
        'desktop_time' => $data['desktop_time'],
        'mobile_time'  => $data['mobile_time'],
        'test_Time_1'  => $data['test_Time'],
        'total_time'   => $data['total_time'],
        'type_test_2'  => $data['type_test_2'],
        'date_test_2'  => $data['date_test_2'],
        'score_test_2' => $data['score_test_2'],
        'level_test_2' => $data['level_test_2'],
        'type_test_3'  => $data['type_test_3'],
        'date_test_3'  => $data['date_test_3'],
        'score_test_3' => $data['score_test_3'],
        'level_test_3' => $data['level_test_3'],
      ];
      // return $batchInsertData;
      // Result::insert($batchInsertData);

    }

    DB::transaction(function () use ($batchInsertData) {
      if (!empty($batchInsertData)) {
          $chunks = array_chunk($batchInsertData, 1000);
          foreach ($chunks as $chunk) {
              Result::insert($chunk);
          }
      }
  });
    // dd($batchInsertData);
    // Batch insert

    // return $batchInsertData;

    return response()->json([
      'message' => 'Data inserted successfully',
      'status'  => 'success',
    ]);
  }




public function paperInsert(Request $request){
  return $request->all();
}
}
