<?php

namespace App\Http\Controllers\api;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class LanguageController extends Controller
{
     // Retrieve all languages
     public function index()
     {
         $languages = Language::all();
         return response()->json(['success' => true, 'languages' => $languages], 200);
     }
    // Store a new language
    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
        ]);
        if (DB::table('languages')->where('libelle',strtolower($request->libelle))->exists()){
            return response()->json(['success' => true, 'message' => $request->Llibelle ." deja exists"], 201);
        }else{
            Language::create([
                'libelle' => strtolower($request->libelle),
            ]);
            return response()->json(['success' => true,'message' => $request->libelle ." created"], 201);
        }

    }



    // Show a specific language
    public function show($id)
    {
        $language = Language::find($id);

        if (!$language)  return response()->json(['success' => false,"message" => "language not found"], 404);
        return response()->json(['success' => true, 'language' => $language], 200);

    }

    // Update a specific language
    public function update(Request $request, $id)
    {
        $language = Language::find($id);
        if (!$language)  return response()->json(['success' => false,"message" => "language not found"], 404);
        $request->validate([
            'libelle' => 'required|string|max:255,' . $language->id,
        ]);

        $language->update([
            'libelle' => strtolower($request->libelle),
        ]);

        return response()->json(['success' => true, 'language' => $language], 200);
    }

    // Delete a specific language
    public function destroy($id)
    {
        $language = Language::find($id);

        if (!$language) {
            return response()->json(['success' => false,"message" => "language not found"], 404);
        }

        $language->delete();
        return response()->json(['success' => true,"message" => "language deleted successfully"], 200);
    }
}
