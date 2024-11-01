<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('App_Rosetta')->plainTextToken;
        $success['name'] =  $user->name;
   
        return $this->sendResponse($success, 'User register successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        // if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            // $user = Auth::user(); 

        $user = User::where('email', $request->email)->first();
        if($user ){ 
            if ($request->filled('password')) {
                if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                    // Authentification réussie avec mot de passe
                    $user = Auth::user();
                } else {
                    // Si le mot de passe est incorrect
                    return $this->sendError('Unauthorised.', ['error' => 'Invalid password.']);
                }
            } else {
                // Authentification avec email uniquement (sans mot de passe)
                Auth::login($user); // Authentification manuelle
            }
            $success['token'] =  $user->createToken('App_Rosetta')->plainTextToken; 
            $success['firstname'] =  $user->firstname;
            $success['lastname'] =  $user->lastname;
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }
}
