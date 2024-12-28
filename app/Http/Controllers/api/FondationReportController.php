<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Str;

class FondationReportController extends Controller {
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
    $request->validate(['csv_path' => 'required']);
    try {
      Log::info("Job started for file: {$request->csv_path}");
      // dd($filePath);
      $data = $this->processCSV($request->csv_path);
      // dd($data);
      if (empty($data)) {
        Log::warning('No valid data found in the CSV file.');

        return;
      }
      // Regrouper par langue
      $groupedByLangue = collect($data)->groupBy('langue')->map(function ($students, $langue) {
        return $students->groupBy('email')->map(function ($userCourses, $email) use ($langue) {
          // dd($userCourses);

          return [
            'email'  => $email,
            'langue' => $langue, // Ajouter la langue ici
            // 'cours_progress' => $userCourses->pluck('cours_progress')->unique()->values()->all(), // Collect unique progress values
            // 'cours_grade' => $userCourses->pluck('cours_grade')->unique()->values()[0], // Collect unique grade values
            'cours' => $userCourses->groupBy('cours_name')->map(function ($lessons, $coursName) {
              return [
                'cours_progress' => $lessons->pluck('cours_progress')->unique()->values()[0], // Collect unique progress values
                'cours_grade' => $lessons->pluck('cours_grade')->unique()->values()[0], // Collect unique grade values
                'cours_name' => $coursName,
                'total_lessons'  => null,
                // 'total_lessons'  => $lessons->pluck('lesson_name')->unique()->count(),
              ];
            })->values(), // Structure propre pour 'cours'
          ];
        })->values(); // Assure un tableau indexé propre pour chaque langue
      })->values(); // Retour propre sans index inutiles

// Ensure the 'builder' directory exists
      $builderDir = storage_path('builder');
      if (!file_exists($builderDir)) {
        mkdir($builderDir, 0755, true); // Create the directory if it doesn't exist
      }

// Define the storage path
      $filePath = $builderDir . DIRECTORY_SEPARATOR . 'data_builder.json';

// Save the grouped data to a JSON file
      file_put_contents($filePath, json_encode($groupedByLangue, JSON_PRETTY_PRINT));

      // return response()->json(['message' => 'Data processed and stored successfully.', 'file_path' => $filePath]);

      return $groupedByLangue;

    } catch (\Exception $e) {
      Log::error("Job failed with exception: {$e->getMessage()}", [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e; // Let Laravel mark the job as failed
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
