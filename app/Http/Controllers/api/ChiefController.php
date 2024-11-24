<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Chief;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;

class ChiefController extends Controller {
  public function index() {
    // Retrieve all chiefs with related institution and user
    return response()->json(Chief::with(['institution', 'user'])->get());
  }

  public function store(Request $request) {
    // Validate the request data
    $validated = $request->validate([
      'institution' => 'required|exists:institutions,libelle',
      'email'       => 'required|exists:users,email',
    ], ['email.exists' => 'this email not existing ']);

    $chief = Institution::where('libelle', strtolower($validated['institution']))->first();
    $user  = User::where('email', strtolower($validated['email']))->first();
    // Create a new chief
    $chief = Chief::create([
      'institution_id' => $chief->id,
      'user_id'        => $user->id,
    ]);

    return response()->json($chief, 201);
  }

  public function show(Chief $chief) {
    // Return the chief with related data
    return response()->json($chief->load(['institution', 'user']));
  }

  public function update(Request $request, Chief $chief) {
    // Validate the request data
    $validated = $request->validate([
      'institution' => 'sometimes|exists:institutions,libelle',
      'email'       => 'sometimes|exists:users,email',
    ], ['email.exists' => 'this email not existing ']);
    $chiefa = Institution::where('libelle', strtolower($validated['institution']))->first();
    $user   = User::where('email', strtolower($validated['email']))->first();

    // Update the chief
    $chief->update([
      'institution_id' => $chiefa->id,
      'user_id'        => $user->id,
    ]);

    return response()->json($chief);
  }

  public function destroy(Chief $chief) {
    // Delete the chief
    $chief->delete();

    return response()->json(['success' => true, 'message' => 'Chief deleted successfully'], 200);
  }

}
