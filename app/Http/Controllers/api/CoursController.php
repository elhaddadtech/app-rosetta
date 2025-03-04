<?php

namespace App\Http\Controllers\api;

use App\Exports\CouresExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CoursController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index() {
    //
  }

  /**
   * Show the form for creating a new resource.
   */
  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request) {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id) {

    // $results = Result::where('language_id', $id)->get();

    // return response()->json($results);

    $courses = DB::table('courses')
      ->join('results', 'courses.result_id', '=', 'results.id')
      ->join('students', 'students.id', '=', 'results.student_id')
      ->join('users', 'students.user_id', '=', 'users.id')
      ->join('languages', 'languages.id', '=', 'results.language_id') // Join the languages table
      ->where('users.email', trim(strtolower($id)))
      ->select('courses.*', 'results.score_test_1', 'results.level_test_1', 'results.total_time', 'languages.libelle as langue') // Select all columns from courses and the langue (libelle) from languages
      ->get()->groupBy('file');

// To return the data, you can do something like:

    return response()->json($courses);
  }

  /**
   * Show the form for editing the specified resource.
   */
  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id) {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id) {
    //
  }

  public function exportCoures() {
    set_time_limit(400);

    return Excel::download(new CouresExport(), 'coures_details.csv', \Maatwebsite\Excel\Excel::CSV);
  }

  public function calculateNotes() {
    // Get scaled scores mapping
    $scaled_scores = DB::table('range_cefefrs')->get();

// Fetch results data
    $results = DB::table('results as r')
      ->join('languages as l', 'r.language_id', '=', 'l.id')
      ->join('students as s', 'r.student_id', '=', 's.id')
      ->join('users as u', 's.user_id', '=', 'u.id')
      ->join('institutions as i', 's.institution_id', '=', 'i.id')
      ->leftJoin('groups as g', 's.group_id', '=', 'g.id')
      ->leftJoin('branches as b', 's.branch_id', '=', 'b.id')
      ->leftJoin('semesters as sm', 's.semester_id', '=', 'sm.id')
      ->select([
        'u.email as user_email',
        'l.libelle as language_libelle',
        'r.student_id',
        'r.language_id',
        DB::raw('MAX(sm.libelle) as semester_libelle'),
        DB::raw('MAX(r.score_test_4) as score_test_4'),
      ])
      ->whereNotNull('r.score_test_4') // Ensures score_test_4 is not NULL
      ->whereRaw('LOWER(r.file) = LOWER(?)', [strtolower('learnergrowth_2025-02-27')]) // Case-insensitive file filter
      ->groupBy('u.email', 'l.libelle', 'r.student_id', 'r.language_id') // Group by unique `email` and `language`
      ->get();

// Process results and calculate note_ceef
    foreach ($results as &$result) {
      // Extraire et nettoyer score_test_4
      $score_test_4 = isset($result->score_test_4) ? explode('/', $result->score_test_4)[0] : null;
      $score_test_4 = is_numeric(trim($score_test_4)) ? (int) trim($score_test_4) : null;
      $language     = strtolower($result->language_libelle);
      $semester     = isset($result->semester_libelle) ? strtolower($result->semester_libelle) : null;

      // Si le semestre est NULL, attribuer "Semestre non défini"
      if (is_null($semester)) {
        $result->note_ceef = 'Semestre non défini';
      } else {
        $note_ceef = null;

        // Trouver le scaled_score correspondant
        foreach ($scaled_scores as $range) {
          if (strtolower($range->language) === $language && strtolower($range->semester) === $semester) {
            $scaled_score = $range->scaled_score;
            if ($score_test_4 == 0) {
              $note_ceef = '0';
            } elseif ($score_test_4 <= $scaled_score) {
              // Appliquer la règle de trois
              $calculated_note = ($score_test_4 / $scaled_score) * 10;
              $note_ceef       = round($calculated_note, 2);
            } elseif ($score_test_4 > $scaled_score) {
              // Appliquer la règle de trois
              $calculated_note = ($score_test_4 - $scaled_score) / (400 - $scaled_score) * 10 + 10;
              $note_ceef       = round($calculated_note, 2);
            } else {
              $note_ceef = 'Note non disponible';
            }

          }
        }

        // Si aucune note n'a été attribuée
        if (is_null($note_ceef)) {
          $note_ceef = 'Note non disponible';
        }

        $result->note_ceef = $note_ceef;
      }

      //  Récupérer `result_id` correspondant dans la table `results`
      $result_id = DB::table('results')
        ->where('student_id', $result->student_id)
        ->where('language_id', $result->language_id)
        ->value('id'); // Récupérer `id` de la table `results`

      //  Mettre à jour la table `courses` avec la nouvelle note_ceef
      if ($result_id) {
        DB::table('courses')
          ->where('result_id', $result_id)
          ->update(['note_ceef' => $result->note_ceef]);
      }
    }

// Nettoyer la référence
    unset($result);

// Now $results contains note_ceef based on language, scaled score, and semester.

    return response()->json([
      'status'  => true,
      'message' => 'Notes calculées avec succès',
      'results' => $results,
      // 'scaled_scores' => $scaled_scores,
    ]);
  }

  public function query() {
    $scaled_scores = DB::table('range_cefefrs')
      ->get();
    $results = DB::table('results as r')
      ->join('languages as l', 'r.language_id', '=', 'l.id')
      ->join('students as s', 'r.student_id', '=', 's.id')
      ->join('users as u', 's.user_id', '=', 'u.id')
      ->join('institutions as i', 's.institution_id', '=', 'i.id')
      ->leftJoin('groups as g', 's.group_id', '=', 'g.id')
      ->leftJoin('branches as b', 's.branch_id', '=', 'b.id')
      ->leftJoin('semesters as sm', 's.semester_id', '=', 'sm.id')
      ->select([
        'u.email as user_email',
        'l.libelle as language_libelle',
        // DB::raw('MAX(u.first_name) as first_name'),
        // DB::raw('MAX(u.last_name) as last_name'),
        // DB::raw('MAX(i.libelle) as institution_libelle'),
        // DB::raw('MAX(b.libelle) as branch_libelle'),
        // DB::raw('MAX(g.libelle) as group_libelle'),
        DB::raw('MAX(sm.libelle) as semester_libelle'),
        // DB::raw('MAX(r.total_time) as total_time'),
        // DB::raw('MAX(r.score_test_1) as score_test_1'),
        // DB::raw('MAX(r.level_test_1) as level_test_1'),
        // DB::raw('MAX(r.level_test_2) as level_test_2'),
        // DB::raw('MAX(r.score_test_2) as score_test_2'),
        // DB::raw('MAX(r.score_test_3) as score_test_3'),
        // DB::raw('MAX(r.level_test_3) as level_test_3'),
        DB::raw('MAX(r.score_test_4) as score_test_4'),
        // DB::raw('MAX(r.level_test_4) as level_test_4'),
      ])
      ->whereNotNull('r.score_test_4') // Ensures score_test_4 is not NULL
      ->whereRaw('LOWER(r.file) = LOWER(?)', [strtolower('learnergrowth_2025-02-10')]) // Case-insensitive file filter
      // ->whereRaw('LOWER(i.libelle) = LOWER(?)', ['fssm']) // Case-insensitive institution filter
      ->groupBy('u.email', 'l.libelle') // Group by unique `email` and `language`
      ->get();
  }

}
