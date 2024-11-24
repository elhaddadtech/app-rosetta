<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Imports\TeachersImport;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class TeacherController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function importTeachers(Request $request) {
    $validator = Validator::make($request->all(), [
      'csv_file' => 'required|mimes:csv,txt',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'error' => $validator->errors(),
      ], 422);
    }

    // Import the CSV file using the TeachersImport class
    $import = new TeachersImport();

    Excel::import($import, $request->file('csv_file'));
    if (!empty($import->getErrors())) {
      $formattedErrors = [];

      // Format errors into a proper array
      foreach ($import->getErrors() as $error) {
        $formattedErrors[] = "Row {$error['row']}: " . implode(', ', $error['errors']);
      }

      return response()->json([
        'status' => 'error',
        'errors' => $formattedErrors, // Include all formatted errors
      ], 422);
    } else {
      return response()->json([
        'status'            => 'success',
        'imported_teachers' => $import->count-1,
        'message'           => 'CSV file imported successfully',
      ], 200);

    }
  }
  public function index() {
    $teachers = Teacher::all();
    // $teachers = Teacher::with(['group', 'branch', 'institution', 'user'])->get();

    return response()->json($teachers);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create() {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request) {
    $validated = $request->validate([
      'status'         => 'required|in:vac,Permanent',
      'role_teach'     => 'required|in:Mentor,Prof,all',
      'group_id'       => 'required|exists:groups,id',
      'branch_id'      => 'required|exists:branches,id',
      'institution_id' => 'required|exists:institutions,id',
      'user_id'        => 'required|exists:users,id',
    ]);

    $teacher = Teacher::create($validated);

    return response()->json([
      'message' => 'Teacher created successfully',
      'teacher' => $teacher,
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(Teacher $teacher) {
    $teacher->load(['group', 'branch', 'institution', 'user']);

    return response()->json($teacher);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id) {
    $teacher = Teacher::findOrFail($id); //1
    $teacher->load(['group', 'branch', 'institution', 'user']);
    return response()->json($teacher);
    // return view('teacher.edit', compact('teacher'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Teacher $teacher) {
    $validated = $request->validate([
      'status'         => 'sometimes|in:vac,Permanent',
      'role_teach'     => 'sometimes|in:Mentor,Prof,all',
      'group_id'       => 'sometimes|exists:groups,id',
      'branch_id'      => 'sometimes|exists:branches,id',
      'institution_id' => 'sometimes|exists:institutions,id',
      'user_id'        => 'sometimes|exists:users,id',
    ]);

    $teacher->update($validated);

    return response()->json([
      'message' => 'Teacher updated successfully',
      'teacher' => $teacher,
    ]);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Teacher $teacher) {
    $teacher->delete();

    return response()->json(['message' => 'Teacher deleted successfully']);
  }
}
