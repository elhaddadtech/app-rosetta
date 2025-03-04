<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
// use App\Imports\UsersImport;
use App\Http\Resources\UserResource;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller {

  public function exportStudents(Request $request) {
    ini_set('max_execution_time', 300);
    // Retrieve institution_id from the request $request->institution_id
    $institution_id = $request->institution_id;
    $institution    = Institution::findOrFail($institution_id);
    // Generate file name with timestamp
    $fileName = 'Students_' . $institution->libelle . '.csv';
    $filePath = storage_path('app/public/' . $fileName); // Define file path

    // Open file for writing
    $handle = fopen($filePath, 'w');

    // Add BOM for UTF-8 encoding
    fwrite($handle, "\xEF\xBB\xBF");

    // Write the CSV header
    fputcsv($handle, [
      'First Name', 'Last Name', 'Email', 'Institution', 'Branch', 'Group',
      'Semester', 'CNE', 'Apogee', 'Birthdate', 'Role',
    ]);

    // Query to get users with their student data filtered by institution_id
    $students = User::whereHas('student', function ($query) use ($institution_id) {
      $query->where('institution_id', $institution_id);
    })->get();

    // Check if any students are found
    if ($students->isEmpty()) {
      return response()->json(['message' => 'No students found for this institution'], 404);
    }

    // Process each student
    foreach ($students as $student) {
      fputcsv($handle, [
        strtolower($student->first_name),
        strtolower($student->last_name),
        strtolower($student->email),
        strtoupper($student->institution_libelle), // assuming this is part of the student model
        strtoupper($student->branch_libelle) ?? 'N/A', // If branch is null, set to N/A
        strtoupper($student->group_libelle) ?? 'N/A', // If group is null, set to N/A
        strtoupper($student->semester_libelle), // Assuming semester information is available
        strtoupper($student->cne),
        $student->apogee,
        $student->birthdate,
        $student->role_libelle,

      ]);
    }

    fclose($handle); // Close the file after writing

    // Return the file as a downloadable response and delete it after sending

    return response()->download($filePath)->deleteFileAfterSend(true);

  }

  public function index() {
    $students    = User::paginate(30); // Vous pouvez ajuster le nombre d'éléments par page ici
    $institution = Institution::all()->unique('libelle');

    return response()->json([
      'students'     => $students,
      'institutions' => $institution,
    ]);

  }

  public function show(User $user) {
    return new UserResource($user->load('role')); // Return a single UserResource
  }

  public function store(Request $request) {

    $validatedData = $request->validate([
      'firstname' => 'required|string|max:45',
      'lastname'  => 'required|string|max:45',
      'email'     => 'required|string|email|max:100|unique:users,email',
      'id_role'   => 'required|exists:roles,id',
    ]);

    $user = User::create($validatedData);

    return new UserResource($user); // Return the created user as UserResource
  }

  public function update(Request $request, User $user) {
    $validatedData = $request->validate([
      'firstname' => 'sometimes|required|string|max:45',
      'lastname'  => 'sometimes|required|string|max:45',
      'email'     => 'sometimes|required|string|email|max:100|unique:users,email,' . $user->id,
      'id_role'   => 'sometimes|required|exists:roles,id',
    ]);

    $user->update($validatedData);

    return new UserResource($user); // Return the updated user as UserResource
  }

  /**
   * Remove the specified user from storage.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Http\Response
   */
  public function destroy($id) {
    $user = User::find($id);

    if (!$user) {
      return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    $user->delete();

    return response()->json(['success' => true, 'message' => 'User deleted successfully'], 200);
  }

}
