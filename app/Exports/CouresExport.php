<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CouresExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize {
  protected $page;
  protected $recordsPerPage;

  /**
   * Constructor to initialize pagination.
   *
   * @param int $page
   * @param int $recordsPerPage
   */
  public function __construct(int $page = 1, int $recordsPerPage = 1000) {
    $this->page           = $page;
    $this->recordsPerPage = $recordsPerPage;
  }

  /**
   * Define the query to fetch data for export with pagination.
   *
   * @return \Illuminate\Database\Query\Builder
   */
  public function query() {
    $offset = ($this->page - 1) * $this->recordsPerPage;

    return DB::table('results as r')
      ->join('languages as l', 'r.language_id', '=', 'l.id')
      ->join('students as s', 'r.student_id', '=', 's.id')
      ->join('users as u', 's.user_id', '=', 'u.id')
      ->join('institutions as i', 's.institution_id', '=', 'i.id')
      ->join('courses as c', 'c.result_id', '=', 'r.id')
      ->select([
        'c.file',
        'c.cours_name',
        'c.cours_progress',
        'c.cours_grade',
        'c.total_lessons',
        'u.first_name',
        'u.last_name',
        'u.email as user_email',
        'l.libelle as language_libelle',
        'i.libelle as institution_libelle',
      ])
      ->orderBy('c.total_lessons')
      ->offset($offset)
      ->limit($this->recordsPerPage);
  }

  /**
   * Define the headings for the CSV export.
   *
   * @return array
   */
  public function headings(): array {
    return [
      'File',
      'Course Name',
      'Course Progress',
      'Course Grade',
      'Total Lessons',
      'First Name',
      'Last Name',
      'Email',
      'Language',
      'Institution',
    ];
  }

  /**
   * Map data for each row before export.
   *
   * @param $row
   * @return array
   */
  public function map($row): array {
    return [
      $row->file,
      $row->cours_name,
      $row->cours_progress,
      $row->cours_grade,
      $row->total_lessons,
      $row->first_name,
      $row->last_name,
      $row->user_email,
      $row->language_libelle,
      $row->institution_libelle,
    ];
  }
}
