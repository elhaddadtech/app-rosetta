<?php

namespace  App\Http\Controllers\api;
use App\Models\Role;
use App\Models\Group;
use App\Models\Branche;
use App\Models\Semester;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CsvUploadController extends Controller
{
   
   
    public function importUsersFromCsv(Request $request)
{
    $request->validate([
        'csv_file' => 'required|file|mimes:csv,txt'
    ]);

    $csvFile = $request->file('csv_file');
    $csvData = file_get_contents($csvFile->getRealPath());
    $rows = explode("\n", $csvData);
    $headerRow = array_shift($rows);
    $totalRows = count($rows);
    $batchSize = max(100, min(1000, (int)($totalRows * 0.1)));

    DB::beginTransaction();
    try {
        $studentsArray = [];
        $duplicates = [];
        $importedCount = 0;

        foreach ($rows as $index => $row) {
            if (empty(trim($row))) continue;

            $row = mb_convert_encoding($row, 'UTF-8', 'UTF-8');
            $data = str_getcsv($row, ';');

            if (count($data) < count(str_getcsv($headerRow, ';'))) {
                Log::info("Row $index skipped due to insufficient columns");
                continue;
            }

            // Retrieve or create institution ID only if it is not empty
            $institutionName = strtoupper($data[5]);
            if (!empty($institutionName)) {
                $institutionId = Institution::where('Libelle', $institutionName)->value('id') 
                    ?? DB::table('institutions')->insertGetId([
                        'Libelle' => $institutionName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                Log::info("Row $index skipped: institution name is empty");
                continue;
            }

            // Retrieve Role by Libelle
            $role = Role::where('Libelle', strtolower($data[10]))->first();
            if (!$role) {
                Log::info("Row $index skipped: Role not found for " . $data[7]);
                continue;
            }

            // Log retrieved IDs
            Log::info('Retrieved Role ID: ' . $role->id);
            Log::info('Retrieved Institution ID: ' . $institutionId);

            // User data
            $firstname = strtolower($data[0]) ?? null;
            $lastname = strtolower($data[1]) ?? null;
            $email = strtolower($data[2]) ?? null;
            $dateNaissance = $data[9] ?? null;
            $idRole = $role->id;

            // Check for valid email ending
            if (empty($firstname) || empty($lastname)) {
                Log::info("Row $index skipped: missing firstname or lastname");
                continue;
            }

            if (empty($email) || !preg_match('/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/', $email)) {
                Log::info("Row $index skipped: invalid email");
                continue;
            }

            if (DB::table('users')->where('email', $email)->exists()) {
                $duplicates[] = $email;
                continue;
            }

            if (!empty($dateNaissance)) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateNaissance);
                if ($date) {
                    $dateNaissance = $date->format('Y-m-d');
                } else {
                    Log::info("Row $index skipped: invalid date format in $dateNaissance");
                    continue;
                }
            }

            // Insert user data
            $userId = DB::table('users')->insertGetId([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'id_role' => $idRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Prepare student data
            $studentsArray[] = [
                'CNE' => strtolower($data[3]),
                'Apogee' => strtolower($data[4]),
                'date_naissance' => $dateNaissance,
                'id_institution' => $institutionId,
                'id_user' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $importedCount++;

            // Insert students in batches
            if (count($studentsArray) >= $batchSize) {
                DB::table('students')->insert($studentsArray);
                $studentsArray = [];
            }
        }

        // Insert any remaining student data
        if (!empty($studentsArray)) {
            DB::table('students')->insert($studentsArray);
        }

        DB::commit();

        if (!empty($duplicates)) {
            Log::info('Skipped duplicate emails: ' . implode(', ', $duplicates));
        }

        return response()->json([
            'success' => true,
            'message' => 'CSV file imported successfully.',
            'imported_count' => $importedCount,
            'duplicates_count' => count($duplicates),
            'duplicates' => $duplicates
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Import error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error importing CSV file: ' . $e->getMessage(),
            'duplicates' => $duplicates
        ], 500);
    }
}

    

    // update users-student

    public function importUpdateUsersFromCsv(Request $request)
    {
        set_time_limit(120);
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);
        
        $csvFile = $request->file('csv_file');
        $csvData = file_get_contents($csvFile->getRealPath());
        $rows = explode("\n", $csvData);
        $headerRow = array_shift($rows);
        $totalRows = count($rows);
        $batchSize = max(100, min(1000, (int)($totalRows * 0.1)));
    
        DB::beginTransaction();
        try {
            $studentsArray = [];
            $duplicates = [];
            $importedCount = 0;
            $errors = [];
    
            foreach ($rows as $index => $row) {
                if (empty(trim($row))) continue;
    
                $row = mb_convert_encoding($row, 'UTF-8', 'UTF-8');
                $data = str_getcsv($row, ';');
    
                if (count($data) < count(str_getcsv($headerRow, ';'))) {
                    $errors[] = "Row $index skipped due to insufficient columns";
                    continue;
                }
    
                // Institution
                $institutionName = strtoupper($data[5]);
                $institution = Institution::where('Libelle', $institutionName)->first();
                $institutionId = $institution ? $institution->id : Institution::create([
                    'Libelle' => $institutionName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->id;
    
                // Group
                $groupName = strtoupper($data[6]);
                $group = Group::where('Libelle', $groupName)->first();
                $groupId = $group ? $group->id : Group::create([
                    'Libelle' => $groupName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->id;
    
                // Branche
                $branchName = strtoupper($data[7]);
                $branch = Branche::where('Libelle', $branchName)->first();
                $branchId = $branch ? $branch->id : Branche::create([
                    'Libelle' => $branchName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->id;
    
                // Semester
                $semesterName = strtoupper($data[8]);
                $semester = Semester::where('Libelle', $semesterName)->first();
                $semesterId = $semester ? $semester->id : Semester::create([
                    'Libelle' => $semesterName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->id;
    
                // Retrieve Role by Libelle
                $role = Role::where('Libelle', strtolower($data[10]))->first();
                if (!$role) {
                    $errors[] = "Row $index skipped: Role not found for " . $data[10];
                    continue;
                }
    
                // User data
                $firstname = strtolower($data[0]) ?? null;
                $lastname = strtolower($data[1]) ?? null;
                $email = strtolower($data[2]) ?? null;
                $dateNaissance = $data[9] ?? null;
                $idRole = $role->id;
    
                // Check for valid email ending
                if (empty($firstname) || empty($lastname)) {
                    $errors[] = "Row $index skipped: missing firstname or lastname";
                    continue;
                }
    
                if (empty($email) || !preg_match('/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/', $email)) {
                    $errors[] = "Row $index skipped: invalid email";
                    continue;
                }
    
                // Vérification de l'existence d'un utilisateur avec cet email
                $user = DB::table('users')->where('email', $email)->first();
    
                if ($user) {
                    // Mettre à jour l'utilisateur existant
                    DB::table('users')->where('id', $user->id)->update([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'id_role' => $idRole,
                        'updated_at' => now(),
                    ]);
                    $userId = $user->id;
                } else {
                    // Insérer un nouvel utilisateur
                    $userId = DB::table('users')->insertGetId([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'id_role' => $idRole,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
    
                // Préparer les données des étudiants
                $studentsArray[] = [
                    'CNE' => strtolower($data[3]),
                    'Apogee' => strtolower($data[4]),
                    'date_naissance' => $dateNaissance,
                    'id_group' => $groupId,
                    'id_branch' => $branchId,
                    'id_semester' => $semesterId,
                    'id_institution' => $institutionId,
                    'id_user' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
    
                $importedCount++;
    
                // Insérer les étudiants en lots
                if (count($studentsArray) >= $batchSize) {
                    DB::table('students')->upsert($studentsArray, ['CNE'], [
                        'Apogee', 'date_naissance', 'id_group', 'id_branch', 'id_semester', 'id_institution', 'id_user', 'updated_at'
                    ]);
                    $studentsArray = [];
                }
            }
    
            // Insérer les données restantes des étudiants
            if (!empty($studentsArray)) {
                DB::table('students')->upsert($studentsArray, ['CNE'], [
                    'Apogee', 'date_naissance', 'id_group', 'id_branch', 'id_semester', 'id_institution', 'id_user', 'updated_at'
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'CSV file imported and updated successfully.',
                'imported_count' => $importedCount,
                'duplicates_count' => count($duplicates),
                'duplicates' => $duplicates,
                'errors' => $errors
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing CSV file: ' . $e->getMessage(),
                'duplicates' => $duplicates,
                'errors' => $errors
            ], 500);
        }
    }
    
    

    public function importGroupsFromCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);
    
        $csvFile = $request->file('csv_file');
        $csvData = file_get_contents($csvFile->getRealPath());
        $rows = explode("\n", $csvData);
        $headerRow = array_shift($rows);
    
        DB::beginTransaction();
        try {
            $duplicates = [];
            $groupsArray = []; // Initialize the groups array
    
            foreach ($rows as $index => $row) {
                if (empty(trim($row))) continue;
    
                $row = mb_convert_encoding($row, 'UTF-8', 'UTF-8');
                $data = str_getcsv($row, ';');
                $Libelle = strtolower($data[0]) ?? null;
    
                // Check for duplicates
                if (DB::table('groups')->where('Libelle', $Libelle)->exists()) {
                    $duplicates[] = $Libelle;
                    continue;
                }
    
                // Add new group to the batch array
                $groupsArray[] = [
                    'Libelle' => $Libelle,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            // Insert all new groups in one batch
            if (!empty($groupsArray)) {
                DB::table('groups')->insert($groupsArray);
            }
    
            DB::commit();
    
            if (!empty($duplicates)) {
                Log::info('Skipped duplicate group: ' . implode(', ', $duplicates));
            }
    
            return response()->json(['success' => true, 'message' => 'CSV file imported successfully.', 'duplicates' => $duplicates]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error importing CSV file: ' . $e->getMessage(), 'duplicates' => $duplicates], 500);
        }
    }
    
     
        


    
}
    
   
