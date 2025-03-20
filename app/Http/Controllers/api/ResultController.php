<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Branche;
use App\Models\DoTest;
use App\Models\Institution;
use App\Models\Language;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller {

  public function index() {
    // Fetch all results with student and language relations
    $results = Result::with(['course'])
      ->orderBy('created_at', 'desc') // Order by latest inserted
      ->paginate(30);
    $institutions = Institution::all()->unique('libelle');

    // Transform each result
    $transformedResults = $results->through(function ($result) {
      // Extract first course if available
      $firstCourse = $result->course->first();

      return [
        'id'                  => $result->id,
        'type_test_1'         => $result->type_test_1,
        'date_test_1'         => $result->date_test_1,
        'score_test_1'        => $result->score_test_1,
        'level_test_1'        => $result->level_test_1,
        'test_time_1'         => $result->test_time_1,
        'desktop_time'        => $result->desktop_time,
        'mobile_time'         => $result->mobile_time,
        'total_time'          => $result->total_time,
        'type_test_2'         => $result->type_test_2,
        'date_test_2'         => $result->date_test_2,
        'score_test_2'        => $result->score_test_2,
        'level_test_2'        => $result->level_test_2,
        'type_test_3'         => $result->type_test_3,
        'date_test_3'         => $result->date_test_3,
        'score_test_3'        => $result->score_test_3,
        'level_test_3'        => $result->level_test_3,
        'type_test_4'         => $result->type_test_4,
        'date_test_4'         => $result->date_test_4,
        'score_test_4'        => $result->score_test_4,
        'level_test_4'        => $result->level_test_4,
        'student_id'          => $result->student_id,
        'language_id'         => $result->language_id,
        'file'                => $result->file,
        'language_libelle'    => $result->language_libelle,
        'email'               => $result->email,
        'full_name'           => $result->full_name,
        'cne'                 => $result->cne,
        'apogee'              => $result->apogee,
        'group_libelle'       => $result->group_libelle,
        'semester_libelle'    => $result->semester_libelle,
        'institution_libelle' => $result->institution_libelle,
        'branch_libelle'      => $result->branch_libelle,

        // Include course data if available
        'total_lessons'       => $firstCourse ? $firstCourse->total_lessons : null,
        'noteCC1'             => $firstCourse ? $firstCourse->noteCC1 : null,
        'noteCC2'             => $firstCourse ? $firstCourse->noteCC2 : null,
        'noteCC'              => $firstCourse ? $firstCourse->noteCC : null,
        'note_ceef'           => $firstCourse ? $firstCourse->note_ceef : null,
      ];
    });

    // Return transformed response with pagination metadata

    return response()->json([
      'results'     => $transformedResults,
      'institution' => $institutions,
    ]);

  }

  public function store(Request $request) {
    // Validate the input
    $validated = $request->validate([
      'results'                => 'required|array', // Expecting an array of results
      'results.*.Test_1'       => 'required|string|max:45',
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
          'result_id'  => $result->id, // Use the result's ID
          'student_id' => $student->user_id,
          'year'       => Carbon::now()->year,
        ]
      );
    }

    return response()->json(['status' => 'success', 'message' => 'Results stored successfully'], 201);
  }

  public function show(Request $request) {
    // Validate input
    $validated = $request->validate([
      'searchInput' => 'nullable|string', // Allow empty values
    ]);

    $search = $validated['searchInput'] ?? null;

    // Query with filtering if search is provided
    $resultsQuery = Result::with(['course', 'student.user']);

    if ($search) {
      $resultsQuery->whereHas('student.user', function ($query) use ($search) {
        $query->where('email', 'LIKE', "%$search%")
              ->orWhere('first_name', 'LIKE', "%$search%")
              ->orWhere('last_name', 'LIKE', "%$search%");
      });
    }

    $results = $resultsQuery->paginate(30);

    // Handle case where no results are found
    if ($results->isEmpty()) {
      return response()->json([
        'message' => 'No results found.',
        'results' => [],
      ], );
    }

    // Transform each result
    $transformedResults = $results->through(function ($result) {
      $firstCourse = $result->course->first();
      $student     = $result->student;
      $user        = $student ? $student->user : null;

      return [
        'id'                  => $result->id,
        'type_test_1'         => $result->type_test_1,
        'date_test_1'         => $result->date_test_1,
        'score_test_1'        => $result->score_test_1,
        'level_test_1'        => $result->level_test_1,
        'test_time_1'         => $result->test_time_1,
        'desktop_time'        => $result->desktop_time,
        'mobile_time'         => $result->mobile_time,
        'total_time'          => $result->total_time,
        'type_test_2'         => $result->type_test_2,
        'date_test_2'         => $result->date_test_2,
        'score_test_2'        => $result->score_test_2,
        'level_test_2'        => $result->level_test_2,
        'type_test_3'         => $result->type_test_3,
        'date_test_3'         => $result->date_test_3,
        'score_test_3'        => $result->score_test_3,
        'level_test_3'        => $result->level_test_3,
        'type_test_4'         => $result->type_test_4,
        'date_test_4'         => $result->date_test_4,
        'score_test_4'        => $result->score_test_4,
        'level_test_4'        => $result->level_test_4,
        'student_id'          => $result->student_id,
        'language_id'         => $result->language_id,
        'file'                => $result->file,
        'language_libelle'    => $result->language_libelle,
        'email'               => $user ? $user->email : null,
        'full_name'           => $user ? $user->first_name . ' ' . $user->last_name : null,
        'cne'                 => $student ? $student->cne : null,
        'apogee'              => $student ? $student->apogee : null,
        'group_libelle'       => $result->group_libelle,
        'semester_libelle'    => $result->semester_libelle,
        'institution_libelle' => $result->institution_libelle,
        'branch_libelle'      => $result->branch_libelle,
        'total_lessons'       => $firstCourse ? $firstCourse->total_lessons : null,
        'noteCC1'             => $firstCourse ? $firstCourse->noteCC1 : null,
        'noteCC2'             => $firstCourse ? $firstCourse->noteCC2 : null,
        'noteCC'              => $firstCourse ? $firstCourse->noteCC : null,
        'note_ceef'           => $firstCourse ? $firstCourse->note_ceef : null,
      ];
    });

    return response()->json([
      'results' => $transformedResults,
    ]);
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

  public function searchByInstitution(Request $request) {
    // Validate the request
    $request->validate([
      'institution' => 'required|exists:institutions,libelle',
      'branch'      => 'nullable', // Allow nullable branch_libelle
    ]);

    $institution = $request->input('institution');
    $branch      = $request->input('branch');

    // Query with filtering if institution and/or branch is provided
    $resultsQuery = Result::with(['course', 'student.user']);

    // Filter by institution
    $resultsQuery->whereHas('student.institution', function ($query) use ($institution) {
      $query->where('libelle', 'LIKE', "%$institution%");
    });

    // Filter by branch if provided
    if ($branch) {
      $resultsQuery->whereHas('student.branch', function ($query) use ($branch) {
        $query->where('libelle', 'LIKE', "%$branch%");
      });
    }

    $results = $resultsQuery->paginate(30);

    // Fetch unique branches for the given institution using a corrected query
    $branches = Branche::whereIn('id', function ($query) use ($institution) {
      $query->select('branch_id')
            ->from('students')
            ->join('institutions', 'institutions.id', '=', 'students.institution_id') // Join with the institutions table
            ->where('institutions.libelle', 'LIKE', "%$institution%")
            ->whereNotNull('branch_id')
            ->distinct();
    })->get();

    // Handle case where no results are found
    if ($results->isEmpty()) {
      return response()->json([
        'message' => 'No results found.',
        'results' => [],
      ]);
    }

    // Transform each result
    $transformedResults = $results->through(function ($result) {
      $firstCourse = $result->course->first();
      $student     = $result->student;
      $user        = $student ? $student->user : null;

      return [
        'id'                  => $result->id,
        'type_test_1'         => $result->type_test_1,
        'date_test_1'         => $result->date_test_1,
        'score_test_1'        => $result->score_test_1,
        'level_test_1'        => $result->level_test_1,
        'test_time_1'         => $result->test_time_1,
        'desktop_time'        => $result->desktop_time,
        'mobile_time'         => $result->mobile_time,
        'total_time'          => $result->total_time,
        'type_test_2'         => $result->type_test_2,
        'date_test_2'         => $result->date_test_2,
        'score_test_2'        => $result->score_test_2,
        'level_test_2'        => $result->level_test_2,
        'type_test_3'         => $result->type_test_3,
        'date_test_3'         => $result->date_test_3,
        'score_test_3'        => $result->score_test_3,
        'level_test_3'        => $result->level_test_3,
        'type_test_4'         => $result->type_test_4,
        'date_test_4'         => $result->date_test_4,
        'score_test_4'        => $result->score_test_4,
        'level_test_4'        => $result->level_test_4,
        'student_id'          => $result->student_id,
        'language_id'         => $result->language_id,
        'file'                => $result->file,
        'language_libelle'    => $result->language_libelle,
        'email'               => $user ? $user->email : null,
        'full_name'           => $user ? $user->first_name . ' ' . $user->last_name : null,
        'cne'                 => $student ? $student->cne : null,
        'apogee'              => $student ? $student->apogee : null,
        'group_libelle'       => $result->group_libelle,
        'semester_libelle'    => $result->semester_libelle,
        'institution_libelle' => $result->institution_libelle,
        'branch_libelle'      => $result->branch_libelle,
        'total_lessons'       => $firstCourse ? $firstCourse->total_lessons : null,
        'noteCC1'             => $firstCourse ? $firstCourse->noteCC1 : null,
        'noteCC2'             => $firstCourse ? $firstCourse->noteCC2 : null,
        'noteCC'              => $firstCourse ? $firstCourse->noteCC : null,
        'note_ceef'           => $firstCourse ? $firstCourse->note_ceef : null,
      ];
    });

    return response()->json([
      'results'  => $transformedResults,
      'branches' => $branches, // Unique branches for the institution
    ]);
  }

}
