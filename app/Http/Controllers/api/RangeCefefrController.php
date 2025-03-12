<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\RangeCefefr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RangeCefefrController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index(): JsonResponse {
    return response()->json(RangeCefefr::all());
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request): JsonResponse {
    $data = $request->validate([
      'language'     => 'nullable|string',
      'scaled_score' => 'nullable|integer',
      'semester'     => 'nullable|string',
    ]);

    $rangeCefefr = RangeCefefr::create($data);

    return response()->json($rangeCefefr, 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(RangeCefefr $rangeCefefr): JsonResponse {
    return response()->json($rangeCefefr);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, RangeCefefr $rangeCefefr): JsonResponse {
    $data = $request->validate([
      'language'     => 'nullable|string',
      'scaled_score' => 'nullable|integer',
      'semester'     => 'nullable|string',
    ]);

    $rangeCefefr->update($data);

    return response()->json($rangeCefefr);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(RangeCefefr $rangeCefefr): JsonResponse {
    $rangeCefefr->delete();

    return response()->json(['message' => 'Deleted successfully'], 200);
  }
}
