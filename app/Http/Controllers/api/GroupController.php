<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller {
  // Retrieve all groups
  public function index() {
    $groups = Group::all();

    return response()->json(['success' => true, 'groups' => $groups], 200);
  }

  // Store a new group
  public function store(Request $request) {
    if (empty($request->libelle)) {
      return response()->json(['success' => false, 'message' => 'Libelle is required'], 201);
    }

    $request->validate([
      'libelle' => 'required|string|max:255',
    ]);
    if (DB::table('groups')->where('libelle', strtolower($request->libelle))->exists()) {
      return response()->json(['success' => true, 'message' => $request->libelle . ' deja exists'], 201);
    }
    $group = Group::create([
      'libelle' => strtolower($request->libelle),
    ]);

    return response()->json(['success' => true, 'group' => $group], 201);
  }

  // Show a specific group
  public function show($id) {
    $group = Group::find($id);

    if (!$group) {
      return response()->json(['success' => false, 'message' => 'Group not found'], 404);
    }

    return response()->json(['success' => true, 'Group' => $group], 200);

  }

  // Update a specific group
  public function update(Request $request, $id) {
    $group = Group::find($id);
    if (!$group) {
      return response()->json(['success' => false, 'message' => 'group not found'], 404);
    }

    $request->validate([
      'libelle' => 'required|string|max:255|unique:groups,libelle,' . $group->id,
    ]);

    $group->update([
      'libelle' => $request->libelle,
    ]);

    return response()->json(['success' => true, 'group' => $group], 200);
  }

  // Delete a specific group
  public function destroy($id) {
    $group = Group::find($id);

    if (!$group) {
      return response()->json(['success' => false, 'message' => 'group not found'], 404);
    }

    $group->delete();

    return response()->json(['success' => true, 'message' => 'group deleted successfully'], 200);
  }

  public function importGroups(Request $request) {
    $request->validate([
      'csv_file' => 'required|file|mimes:csv,txt',
    ]);

    $csvFile   = $request->file('csv_file');
    $csvData   = file_get_contents($csvFile->getRealPath());
    $rows      = explode("\n", $csvData);
    $headerRow = array_shift($rows);

    DB::beginTransaction();
    try {
      $duplicates  = [];
      $groupsArray = []; // Initialize the groups array

      foreach ($rows as $index => $row) {
        if (empty(trim($row))) {
          continue;
        }

        $row     = mb_convert_encoding($row, 'UTF-8', 'UTF-8');
        $data    = str_getcsv($row, ';');
        $Libelle = strtolower($data[0]) ?? null;

        // Check for duplicates
        if (DB::table('groups')->where('libelle', $Libelle)->exists()) {
          $duplicates[] = $Libelle;
          continue;
        }

        // Add new group to the batch array
        $groupsArray[] = [
          'libelle'    => $Libelle,
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }

      // Insert all new groups in one batch
      if (!empty($groupsArray)) {
        DB::table('groups')->insert($groupsArray);
      }

      DB::commit();

      if (!empty($duplicates)) {
        Log::info('Skipped duplicate group: ' . implode(', ', $duplicates));
      }

      return response()->json(['success' => true, 'message' => 'CSV file imported successfully.', 'duplicates' => $duplicates]);

    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Import error: ' . $e->getMessage());

      return response()->json(['success' => false, 'message' => 'Error importing CSV file: ' . $e->getMessage(), 'duplicates' => $duplicates], 500);
    }
  }
}
