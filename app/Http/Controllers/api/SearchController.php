<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Branche;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller {
  public function usersSearch(Request $request) {
    $validate = $request->validate([
      'search' => 'required',
    ]);
    $search = strtolower($validate['search']);
    $users  = User::where('first_name', $search)
      ->orWhere('last_name', $search)
      ->orWhere('email', $search)->get();

    return response()->json([
      'Status' => $users->isNotEmpty(),
      'Result' => $users,
    ]);
  }

  public function searchStudents(Request $request) {
    // Validate the search input
    $validated = $request->validate([
      'search' => 'required|string|max:255',
    ]);

    $search = strtolower(trim($validated['search']));

    // Query the students table and related models
    $students = Student::with(['institution', 'group', 'branch', 'semester', 'language', 'user'])
      ->where('cne', $search) // Search in cne column
      ->orWhere('apogee', $search) // Search in apogee column
      ->orWhereRelation('user', 'email', $search) // Search in user email
      ->orWhereRelation('user', 'first_name', $search) // Search in user first_name
      ->orWhereRelation('user', 'last_name', $search) // Search in user last_name
      ->orWhereRelation('group', 'libelle', $search) // Search in related group
      ->orWhereRelation('branch', 'libelle', $search) // Search in related branch
      ->orWhereRelation('semester', 'libelle', $search) // Search in related semester
      ->orWhereRelation('institution', 'libelle', $search) // Search in related institution
      ->get();

    // Transform the students data
    $transformedStudents = $students->map(function ($student) {
      return [
        'id'                  => $student->user?->id,
        'email'               => $student->user?->email,
        'role_id'             => $student->user?->role_id,
        'full_name'           => $student->user?->full_name,
        'role_libelle'        => $student->user?->role_libelle,
        'cne'                 => strtoupper(($student->cne)),
        'apogee'              => $student?->apogee,
        'birthdate'           => $student?->birthdate,
        'institution_libelle' => $student?->institution?->libelle,
        'group_libelle'       => $student?->group?->libelle,
        'branch_libelle'      => $student?->branch?->libelle,
        'semester_libelle'    => $student?->semester?->libelle,
        'first_access'        => $student?->first_access,
        'last_access'         => $student?->last_access,
      ];
    });

    // Find users without related students
    $usersWithoutStudents = User::where('email', $search)
      ->orWhere('first_name', $search)
      ->orWhere('last_name', $search)
      ->whereDoesntHave('student') // Ensures no related student exists
      ->get();
    // dd($usersWithoutStudents);
    // Transform the users without students data
    $transformedUsersWithoutStudents = $usersWithoutStudents->map(function ($user) {
      return [
        'id'           => $user?->id,
        'email'        => $user?->email,
        'full_name'    => $user?->full_name,
        'role_id'      => $user?->role_id,
        'role_libelle' => $user?->role_libelle,
      ];
    });
    // return $usersWithoutStudents;

    // Merge the results
    $result = collect($transformedStudents)->merge(collect($transformedUsersWithoutStudents));

    // Return the response

    return response()->json([
      'status' => $result->isNotEmpty(),
      'result' => $result,
    ]);
  }

  public function searchUsers(Request $request) {
    $validated = $request->validate([
      'email' => 'required|string|max:255',
    ]);

    $search = strtolower(trim($validated['email']));

    // Requête pour récupérer l'étudiant avec les relations nécessaires
    $student = Student::whereHas('user', function ($query) use ($search) {
      $query->where('email', $search); // Vérifie l'email dans la table users
    })
      ->with(['results.course']) // Charger les résultats et les cours associés
      ->first(); // Récupérer le premier étudiant correspondant

    if (!$student) {
      return response()->json([
        'status'  => false,
        'message' => 'Student not found',
      ], 404);
    }

    // return $student?->results;
    // Transformation des données pour optimisation
    $optimizedResult = [
      'id'          => $student?->id,
      'email'       => $student?->user->email ?? 'N/A',
      'full_name'   => $student?->user->full_name ?? 'N/A',
      'institution' => $student?->institution->libelle ?? 'N/A',
      'group'       => $student?->user->group_libelle ?? 'N/A',
      'branch'      => $student?->user->branch_libelle ?? 'N/A',
      'semester'    => $student?->user->semester_libelle ?? 'N/A',
      'cne'         => $student?->cne ?? 'N/A',
      'apogee'      => $student?->apogee ?? 'N/A',
      'birthdate'   => $student?->birthdate ?? 'N/A',
      // 'role'        => 'admin',
      'role'        => $student?->user?->role_libelle ?? null,
      'languages'   => $student?->results->groupBy('language_libelle')->map(function ($results) {
        return $results->map(function ($result) {
          return [
            'id'           => $result->id,
            'type_test_1'  => $result->type_test_1,
            'date_test_1'  => $result->date_test_1,
            'score_test_1' => $result->score_test_1,
            'level_test_1' => $result->level_test_1,
            'total_time'   => $result->total_time,
            'type_test_2'  => $result->type_test_2 == null ? 'N/A' : $result->type_test_2,
            'date_test_2'  => $result->date_test_2 == null ? 'N/A' : $result->date_test_2,
            'score_test_2' => $result->score_test_2 == null ? 'N/A' : $result->score_test_2,
            'level_test_2' => $result->level_test_2 == null ? 'N/A' : $result->level_test_2,

            'type_test_3'  => $result->type_test_3 == null ? 'N/A' : $result->type_test_3,
            'date_test_3'  => $result->date_test_3 == null ? 'N/A' : $result->date_test_3,
            'score_test_3' => $result->score_test_3 == null ? 'N/A' : $result->score_test_3,
            'level_test_3' => $result->level_test_3 == null ? 'N/A' : $result->level_test_3,

            'score_test_4' => $result->score_test_4 == '' ? 'N/A' : $result->score_test_4,
            'level_test_4' => $result->level_test_4 == '' ? 'N/A' : $result->score_test_4,

            'courses'      => $result->course->map(function ($course) {
              return [
                'id'            => $course->id,
                'name'          => $course->cours_name,
                'progress'      => $course->cours_progress,
                'grade'         => $course->cours_grade,
                'total_lessons' => $course->total_lessons,
                'noteCC1'       => $course->noteCC1,
                'noteCC2'       => $course->noteCC2,
                'noteCC'        => $course->noteCC,
                'noteExam'      => $course->note_ceef,
              ];
            }),
          ];
        });
      }),
    ];

    // Retourner les données optimisées

    return response()->json([
      'status' => true,

      'result' => $optimizedResult,
    ]);

  }

  public function StatStudents() {
    $totalUsers               = DB::table('users')->count();
    $totalStudentsWithResults = DB::table('results')->distinct('student_id')->count();
    $usersNotInResults        = DB::table('users')
      ->whereNotIn('id', function ($query) {
        $query->select('student_id')->from('results');
      })
      ->count();

    return response()->json([
      'total_students'          => $totalUsers,
      'total_students_active'   => $totalStudentsWithResults,
      'total_students_inactive' => $usersNotInResults,
    ]);

  }
  public function searchByInstitution(Request $request) {
    // Validate the request
    $request->validate([
      'institution_id' => 'required|integer|exists:institutions,id',
      'branch_id'      => 'nullable', // Allow nullable branch_id (string or integer)
    ]);

    // Convert "null" string to actual null
    $branchId = ($request->branch_id === 'null' || $request->branch_id === null) ? null : (int) $request->branch_id;

    // Get paginated students based on institution_id (and optionally branch_id)
    $studentsQuery = User::whereHas('student', function ($query) use ($request, $branchId) {
      $query->where('institution_id', $request->institution_id);

      // Only filter by branch_id if it's not null
      if (!isset($branchId)) {
        return; // If branch_id is null, don't add additional filtering
      }

      $query->where('branch_id', $branchId);
    });

    $students = $studentsQuery->paginate(30);

    // Get all unique branches where students belong to the given institution
    $branches = Branche::whereIn('id', function ($query) use ($request) {
      $query->select('branch_id')
            ->from('students')
            ->where('institution_id', $request->institution_id)
            ->whereNotNull('branch_id')
            ->distinct();
    })->get();

    return response()->json([
      'students' => $students, // Paginated students
      'branches' => $branches, // Unique branches without duplicates
    ]);
  }

  public function searchByParams(Request $request) {
    $search   = trim(strtolower($request->search));
    $students = User::whereHas('student', function ($q) use ($search) {
      $q->where('first_name', 'like', "%{$search}%")
        ->orWhere('last_name', 'like', "%{$search}%")
        ->orWhere('email', 'like', "%{$search}%");
    })->paginate(30);

    return response()->json([
      'students' => $students,
    ]);
  }

}
