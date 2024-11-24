<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\DoTest;
use App\Models\Language;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller {
  public function index() {
    // Fetch all results with student and language relations
    return response()->json(Result::all());
  }

  public function store(Request $request) {
    // Validate the input
    $validated = $request->validate([
      'results'                => 'required|array', // Expecting an array of results
      'results.*.Test_1' => 'required|string|max:45',
      'results.*.Score_Test_1' => 'required|string|max:45',
      'results.*.Time_PC'      => 'required|string|max:45',
      'results.*.Time_Mobile'  => 'required|string|max:45',
      'results.*.Time_All'     => 'required|string|max:45',
      'results.*.Activity'     => 'required|string|max:45',
      'results.*.Pass_Score'   => 'required|string|max:45',
      'results.*.Score_All'    => 'required|string|max:45',
      'results.*.Test_2'       => 'nullable|string|max:45',
      'results.*.Score_Test_2' => 'nullable|string|max:45',
      'results.*.Test_3'       => 'nullable|string|max:45',
      'results.*.Score_Test_3' => 'nullable|string|max:45',
      'results.*.Test_4'       => 'nullable|string|max:45',
      'results.*.Score_Test_4' => 'nullable|string|max:45',
      'results.*.email'        => 'required|exists:users,email|regex:/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/',
      'results.*.language'     => 'required',
    ],
      [
        'results.*.email.exists' => 'The student with email :input does not exist.',
        'results.*.email.regex'  => "Email {$request->email} must be from the domain @uca.ac.ma.",
      ]);

    $resultsData = $validated['results'];

    // Loop through each result entry and process it
    foreach ($resultsData as $data) {
      $student = DB::table('students')
        ->join('users', 'students.user_id', '=', 'users.id')
        ->where('users.email', strtolower($data['email']))
        ->select('students.user_id')
        ->first();
      if (!$student) {
        return response()->json(['error' => 'Student or User not found'], 404); // This is the user_id from the students table
      }
      $languageA = Language::firstOrCreate([
        'libelle' => strtolower($data['language']),
      ]);
      $data['language_id'] = $languageA->id;
      $data['student_id']  = $student->user_id;
      // return $student;
      unset($data['email'], $data['language']);
      // Create or update Result
      // return $data;
      $result = Result::updateOrCreate([
        'student_id'  => $student->user_id,
        'language_id' => $languageA->id,
      ], $data);
      // Create or update DoTest
      DoTest::updateOrCreate(
        [
          'result_id'  => $result->id,
          'student_id' => $student->user_id,
          'year'       => Carbon::now()->year,
        ],
        [
          'result_id' => $result->id, // Use the result's ID
          'student_id' => $student->user_id,
          'year'      => Carbon::now()->year,
        ]
      );
    }

    return response()->json(['status' => 'success', 'message' => 'Results stored successfully'], 201);
  }

  public function show(Result $result) {
    // Fetch a specific result with relations
    return response()->json($result->load(['student', 'language']));
  }

  public function update(Request $request, Result $result) {
    // Validate the input
    $validated = $request->validate([
      'Test_1'       => 'sometimes|string|max:45',
      'Score_Test_1' => 'sometimes|string|max:45',
      'Time_PC'      => 'sometimes|string|max:45',
      'Time_Mobile'  => 'sometimes|string|max:45',
      'Time_All'     => 'sometimes|string|max:45',
      'Activity'     => 'sometimes|string|max:45',
      'Pass_Score'   => 'sometimes|string|max:45',
      'Score_All'    => 'sometimes|string|max:45',
      'Test_2'       => 'nullable|string|max:45',
      'Score_Test_2' => 'nullable|string|max:45',
      'Test_3'       => 'nullable|string|max:45',
      'Score_Test_3' => 'nullable|string|max:45',
      'Test_4'       => 'nullable|string|max:45',
      'Score_Test_4' => 'nullable|string|max:45',
      'student_id'   => 'sometimes|exists:students,id',
      'language_id'  => 'sometimes|exists:languages,id',
    ]);

    // Update the result
    $result->update($validated);

    return response()->json($result);
  }

  public function destroy(Result $result) {
    // Delete the result
    $result->delete();

    return response()->json(['success' => true, 'message' => 'Result deleted successfully'], 200);
  }
}
