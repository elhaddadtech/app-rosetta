<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BuilderResultExport implements FromCollection, WithHeadings {
  public function headings(): array {
    return [
      'Langue',
      'Email',
      'Cours Name',
      'Total Lessons',
      'cours progress',
      'Cours grade',

    ];
  }

  public function collection() {
    // Path to your JSON file
    $filePath = storage_path('builder/data_builder.json');

    if (!file_exists($filePath)) {
      return collect([]); // Return empty if file doesn't exist
    }

    // Read JSON file
    $jsonData = json_decode(file_get_contents($filePath), true);

    // Flatten data into rows for Excel
    $rows = [];
    foreach ($jsonData as $langueGroup) {
      foreach ($langueGroup as $student) {
        foreach ($student['cours'] as $course) {
          $rows[] = [
            'langue'         => $student['langue'],
            'email'          => $student['email'],
            'cours_name'     => $course['cours_name'],
            'total_lessons'  => $course['total_lessons'],
            'cours_progress' => $course['cours_progress'] == '' ? '0%' : $course['cours_progress'],
            'cours_grade'    => $course['cours_grade'] == '' ? '0%' : $course['cours_grade'],
          ];
        }
      }
    }

    return collect($rows);
  }
}
