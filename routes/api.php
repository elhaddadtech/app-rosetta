<?php

use App\Http\Controllers\api\CsvUploadController;
use App\Http\Controllers\api\GroupController;
use App\Http\Controllers\api\ImportController;
use App\Http\Controllers\api\LanguageController;
use App\Http\Controllers\api\RegisterController;
use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(RegisterController::class)->group(function () {
  Route::post('register', 'register');
  Route::post('login', 'login');
});
Route::middleware('auth:sanctum')->group(function () {
  Route::apiResource('roles', RoleController::class);
  Route::apiResource('users', UserController::class);
});
Route::apiResource('groups', GroupController::class);
Route::apiResource('languages', LanguageController::class);
Route::post('/uploadUsers', [CsvUploadController::class, 'importUsersFromCsv']);
Route::post('/upload-students', [ImportController::class, 'importCSV']);
Route::post('/upload-update-students', [ImportController::class, 'importUpdateCSV']);
Route::post('/upload-updateStudents', [CsvUploadController::class, 'importUpdateStudents']);
Route::post('/uploadUpdateUsers', [CsvUploadController::class, 'importUpdateUsersFromCsv']);
Route::post('/uploadGroups', [CsvUploadController::class, 'importGroupsFromCsv']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
