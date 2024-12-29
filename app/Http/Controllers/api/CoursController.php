<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    $courses = DB::table('courses')
      ->join('results', 'courses.result_id', '=', 'results.id')
      ->join('students', 'students.id', '=', 'results.student_id')
      ->join('users', 'students.user_id', '=', 'users.id')
      ->join('languages', 'languages.id', '=', 'results.language_id') // Join the languages table
      ->where('users.email', 'd.fatimazahra5216@uca.ac.ma')
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
}
