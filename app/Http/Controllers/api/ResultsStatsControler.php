<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use DB;

class ResultsStatsControler extends Controller {
  public function ResultStats() {
    $results = DB::table('results')
    ->join('languages', 'results.language_id', '=', 'languages.id')
    ->select(
        'level_test_1 as level',
        'languages.libelle as language_name',
        DB::raw('COUNT(DISTINCT student_id) as total_students')
    )
    ->whereIn('level_test_1', ['Below A1','A2', 'A1', 'B1', 'B2', 'C1', 'C1+'])
    ->groupBy('level_test_1', 'languages.libelle')
    ->orderByRaw("FIELD(level_test_1, 'A', 'A1', 'B1', 'B2', 'C1', 'C2')")
    ->get();
    return["Total_Etudiants"=> Result::distinct()->count('student_id'), "LearnerGrowth"=>collect($results)->groupBy( 'language_name')];
  }
}
