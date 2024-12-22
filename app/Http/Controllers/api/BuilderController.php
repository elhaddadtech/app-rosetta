<?php

namespace App\Http\Controllers\api;

use App\Exports\BuilderResultExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class BuilderController extends Controller {
  public $dataExcel = [];
  public function impocrtBuilderCSV(Request $request) {
    $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
    $fileName          = $request->file('csv_file')->getClientOriginalName();
    $filePath          = $request->file('csv_file')->storeAs('csv_uploads', $fileName, 'public');
    $fileFullPath      = storage_path('app/public/' . $filePath);
    $handleBuilderFile = $this->handle($fileFullPath);

    return response()->json($handleBuilderFile);
    // dd($handleBuilderFile);

    return 'uploaded';
  }

  public function handle($filePath) {
    try {
      Log::info("Job started for file: {$filePath}");
      // dd($filePath);
      $data = $this->processCSV($filePath);
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
                'total_lessons'  => $lessons->pluck('lesson_name')->unique()->count(),
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
        'email'          => isset($row[$emailIndex]) ? $row[$emailIndex] : null,
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
    $fileName = 'data_builder.xlsx';

    return Excel::download(new BuilderResultExport, $fileName);
    // return Excel::download(new BuilderResultExport($data), 'grouped_data.xlsx');
  }

}
