<?php

namespace App\Http\Controllers\api;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{ 
    // Retrieve all groups
    public function index()
    {
        $groups = Group::all();
        return response()->json(['success' => true, 'groups' => $groups], 200);
    }

    // Store a new group
    public function store(Request $request)
    {
        if (empty($request->Libelle)) return response()->json(['success' => false, 'message' => "Libelle is required"], 201);
        ;
        $request->validate([
            'Libelle' => 'required|string|max:255',
        ]);
        if (DB::table('groups')->where('Libelle',strtolower($request->Libelle))->exists()){
            return response()->json(['success' => true, 'message' => $request->Libelle ." deja exists"], 201);
        }
        $group = Group::create([
            'Libelle' =>strtolower( $request->Libelle),
        ]);

        return response()->json(['success' => true, 'group' => $group], 201);
    }

   

    // Show a specific group
    public function show($id)
    {
        $group = Group::find($id); 

        if (!$group)  return response()->json(['success' => false,"message" => "Group not found"], 404);
        return response()->json(['success' => true, 'Group' => $group], 200);
        
    }

    // Update a specific group
    public function update(Request $request, $id)
    {
        $group = Group::find($id); 
        if (!$group)  return response()->json(['success' => false,"message" => "group not found"], 404);

        $request->validate([
            'Libelle' => 'required|string|max:255|unique:groups,Libelle,' . $group->id,
        ]);

        $group->update([
            'Libelle' => $request->Libelle,
        ]);

        return response()->json(['success' => true, 'group' => $group], 200);
    }

    // Delete a specific group
    public function destroy($id)
    {
        $group = Group::find($id); 

        if (!$group) {
            return response()->json(['success' => false,"message" => "group not found"], 404);
        }

        $group->delete();
        return response()->json(['success' => true,"message" => "group deleted successfully"], 200);
    }
}
