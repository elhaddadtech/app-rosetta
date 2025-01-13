<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

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

    // return $student->user;
    // Transformation des données pour optimisation
    $optimizedResult = [
      'id'          => $student->id,
      'email'       => $student->user->email ?? 'N/A',
      'full_name'   => $student->user->full_name ?? 'N/A',
      'institution' => $student->institution->libelle ?? 'N/A',
      'group'       => $student->user->group_libelle ?? 'N/A',
      'branch'      => $student->user->branch_libelle ?? 'N/A',
      'semester'    => $student->user->semester_libelle ?? 'N/A',
      'cne'         => $student->cne ?? 'N/A',
      'apogee'      => $student->apogee ?? 'N/A',
      'birthdate'   => $student->birthdate ?? 'N/A',
      'languages'   => $student->results->groupBy('language_libelle')->map(function ($results) {
        return $results->map(function ($result) {
          return [
            'id'           => $result->id,
            'type_test_1'  => $result->type_test_1,
            'date_test_1'  => $result->date_test_1,
            'score_test_1' => $result->score_test_1,
            'level_test_1' => $result->level_test_1,
            'total_time'   => $result->total_time,
            'type_test_2'  => $result->type_test_2,
            'date_test_2'  => $result->date_test_2,
            'score_test_2' => $result->score_test_2,
            'level_test_2' => $result->level_test_2,
            'courses'      => $result->course->map(function ($course) {
              return [
                'id'            => $course->id,
                'name'          => $course->cours_name,
                'progress'      => $course->cours_progress,
                'grade'         => $course->cours_grade,
                'total_lessons' => $course->total_lessons,
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

}
