<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class RegisterController extends Controller {
  /**
   * Register api
   *
   * @return \Illuminate\Http\Response
   */
  public function register(Request $request): JsonResponse {
    $validator = Validator::make($request->all(), [
      'name'       => 'required',
      'email'      => 'required|email',
      'password'   => 'required',
      'c_password' => 'required|same:password',
    ]);

    if ($validator->fails()) {
      return response()->json(['error' => $validator->errors()], 401);
    }

    $input             = $request->all();
    $input['password'] = bcrypt($input['password']);
    $user              = User::create($input);
    $success['token']  = $user->createToken('App_Rosetta')->plainTextToken;
    $success['name']   = $user->name;

    return response()->json($success);
  }

  /**
   * Login api
   *
   * @return \Illuminate\Http\Response
   */

  public function login(Request $request): JsonResponse {
    $user = User::where('email', $request->email)->first();

    if ($user) {
      if ($request->filled('password')) {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
          // Authentication successful
          $user = Auth::user();
        } else {
          return response()->json(['error' => 'Unauthorized'], 401);
        }
      } else {
        // Authenticate with email only
        Auth::login($user);
      }

      // Create a token
      $token = $user->createToken('API Token')->plainTextToken;

      return response()->json(['token' => $token, 'firstname' => $user->first_name, 'lastname' => $user->last_name]);
    } else {
      return response()->json(['error' => 'Unauthorized'], 401);
    }
  }

}
