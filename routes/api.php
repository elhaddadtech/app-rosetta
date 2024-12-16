<?php

use App\Http\Controllers\api\CatalystController;
use App\Http\Controllers\api\ChiefController;
use App\Http\Controllers\api\GroupController;
use App\Http\Controllers\api\GrowthReportController;
use App\Http\Controllers\api\ImportController;
use App\Http\Controllers\api\LanguageController;
use App\Http\Controllers\api\RegisterController;
use App\Http\Controllers\api\ResultController;
use App\Http\Controllers\api\ResultsStatsControler;
use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\SearchController;
use App\Http\Controllers\api\TeacherController;
use App\Http\Controllers\api\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(RegisterController::class)->group(function () {
  Route::post('register', 'register');
  Route::post('login', 'login');
});

Route::apiResource('roles', RoleController::class);
Route::middleware('auth:sanctum')->group(function () {});
Route::apiResource('users', UserController::class);
Route::apiResource('groups', GroupController::class);
Route::apiResource('languages', LanguageController::class);
Route::apiResource('teachers', TeacherController::class);
Route::apiResource('chiefs', ChiefController::class);
Route::apiResource('results', ResultController::class);
Route::post('/students/import', [ImportController::class, 'importStudents']);
Route::post('/teachers/import', [TeacherController::class, 'importTeachers']);
Route::post('/Groups/import', [GroupController::class, 'importGroups']);
//Search Api
Route::post('search/user', [SearchController::class, 'searchStudents']);
Route::post('import/growthReport', [GrowthReportController::class, 'import']);
Route::post('test', [CatalystController::class, 'paperInsert']);
Route::get('users/export', [UserController::class, 'export']);

Route::get('learnerGrowth/export', [GrowthReportController::class, 'ExportDataLearnerGrowth']);

// --------------Results_Stats --------------------------------
Route::get('learnerGrowth/stats', [ResultsStatsControler::class, 'ResultStats']);
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// Route::post('/upload-update-students', [ImportController::class, 'importUpdateCSV']);
// Route::post('/upload-updateStudents', [CsvUploadController::class, 'importUpdateStudents']);
// Route::post('/uploadUpdateUsers', [CsvUploadController::class, 'importUpdateUsersFromCsv']);
// Route::post('/uploadUsers', [CsvUploadController::class, 'importUsersFromCsv']);
