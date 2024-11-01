<?php

namespace App\Http\Controllers\api;
use App\Models\User;
// use App\Imports\UsersImport;
use App\Jobs\ProcessCsvJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get(); // Eager load the role relationship
        return UserResource::collection($users); // Return a collection of UserResource
    }
    
    public function show(User $user)
    {
        return new UserResource($user->load('role')); // Return a single UserResource
    }
    
    public function store(Request $request)
    {
    
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:45',
            'lastname' => 'required|string|max:45',
            'email' => 'required|string|email|max:100|unique:users,email',
            'id_role' => 'required|exists:roles,id',
        ]);
    
        $user = User::create($validatedData);
    
        return new UserResource($user); // Return the created user as UserResource
    }
    
    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'firstname' => 'sometimes|required|string|max:45',
            'lastname' => 'sometimes|required|string|max:45',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . $user->id,
            'id_role' => 'sometimes|required|exists:roles,id',
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
    public function destroy($id)
    {
        $user = User::find($id); 

        if (!$user) {
            return response()->json(['success' => false,"message" => "User not found"], 404);
        }

        $user->delete();
        return response()->json(['success' => true,"message" => "User deleted successfully"], 200);
    }
    
// Upload files to database



}
