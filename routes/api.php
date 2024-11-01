<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\GroupController;
use App\Http\Controllers\api\LanguageController;
use App\Http\Controllers\api\RegisterController;
use App\Http\Controllers\api\CsvUploadController;




Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
});
Route::middleware('auth:sanctum')->group( function () {
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);  
});
Route::apiResource('groups', GroupController::class);
Route::apiResource('languages', LanguageController::class);
Route::post('/uploadUsers', [CsvUploadController::class, 'importUsersFromCsv']);
Route::post('/uploadUpdateUsers', [CsvUploadController::class, 'importUpdateUsersFromCsv']);
Route::post('/uploadGroups', [CsvUploadController::class, 'importGroupsFromCsv']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
