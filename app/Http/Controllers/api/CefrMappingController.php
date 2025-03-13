<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CefrMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CefrMappingController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index(): JsonResponse {
    return response()->json(['success' => true, 'data' => CefrMapping::all()]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request): JsonResponse {
    $data = $request->validate([
      'level'              => 'required|string|max:10',
      'language'           => 'required|string|max:20',
      'lesson'             => 'required|integer',
      'seuil_heures_jours' => 'nullable|integer',
      'noteCC1_ratio'      => 'nullable|integer',
      'noteCC2_ratio'      => 'nullable|integer',
    ]);

    $cefrMapping = CefrMapping::create($data);

    return response()->json($cefrMapping, 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(CefrMapping $cefrMapping): JsonResponse {
    return response()->json($cefrMapping);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, CefrMapping $cefrMapping): JsonResponse {
    $data = $request->validate([
      'level'              => 'required|string|max:10',
      'language'           => 'required|string|max:20',
      'lesson'             => 'required|integer',
      'seuil_heures_jours' => 'nullable|integer',
      'noteCC1_ratio'      => 'nullable|integer',
      'noteCC2_ratio'      => 'nullable|integer',
    ]);

    $cefrMapping->update($data);
// If noteCC1_ratio or noteCC2_ratio are updated, update all records
    if (isset($data['noteCC1_ratio']) || isset($data['noteCC2_ratio'])) {
      CefrMapping::query()->update([
        'noteCC1_ratio' => $data['noteCC1_ratio'] ?? $cefrMapping->noteCC1_ratio,
        'noteCC2_ratio' => $data['noteCC2_ratio'] ?? $cefrMapping->noteCC2_ratio,
      ]);
    }

    return response()->json(['success' => true, 'data' => $cefrMapping]);

  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(CefrMapping $cefrMapping): JsonResponse {
    $cefrMapping->delete();

    return response()->json(['message' => 'Deleted successfully'], 200);
  }
}
