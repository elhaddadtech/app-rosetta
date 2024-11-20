<?php

namespace App\Imports;

use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentsImport implements ToCollection {
  public function collection(Collection $rows) {
    $headerRow = $rows->first();
    $totalRows = count($rows);
    $batchSize = max(100, min(1000, (int) ($totalRows * 0.1)));

    DB::beginTransaction();
    try {
      $studentsArray = [];
      $duplicates    = [];
      $importedCount = 0;

      foreach ($rows->slice(1) as $index => $row) {
        $data = $this->parseRow($row, $headerRow, $index);
        if (!$data) {
          continue;
        }

        $institutionId = $this->getInstitutionId($data['institution']);
        if (!$institutionId) {
          continue;
        }

        $roleId = $this->getRoleId($data['role']);
        if (!$roleId) {
          continue;
        }

        if (!$this->isValidEmail($data['email']) || $this->isDuplicateEmail($data['email'])) {
          $duplicates[] = $data['email'];
          continue;
        }

        $userId          = $this->createUser($data, $roleId);
        $studentsArray[] = $this->prepareStudentData($data, $userId, $institutionId);
        $importedCount++;

        if (count($studentsArray) >= $batchSize) {
          DB::table('students')->insert($studentsArray);
          $studentsArray = [];
        }
      }

      if (!empty($studentsArray)) {
        DB::table('students')->insert($studentsArray);
      }

      DB::commit();

      if (!empty($duplicates)) {
        Log::info('Skipped duplicate emails: ' . implode(', ', $duplicates));
      }

      return [
        'success'          => true,
        'imported_count'   => $importedCount,
        'duplicates_count' => count($duplicates),
        'duplicates'       => $duplicates,
      ];

    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Import error: ' . $e->getMessage());
      throw $e;
    }
  }

  private function parseRow($row, $headerRow, $index) {
    // Parse row based on headers and return associative array
    return [
      'institution' => $row[$headerRow->search('institution')],
      'role'        => $row[$headerRow->search('role')],
      'name'        => $row[$headerRow->search('name')],
      'email'       => $row[$headerRow->search('email')],
    ];
  }

  private function getInstitutionId($institutionName) {
    $institution = Institution::firstOrCreate(['name' => $institutionName]);

    return $institution->id;
  }

  private function getRoleId($roleName) {
    $role = Role::firstOrCreate(['name' => $roleName]);

    return $role->id;
  }

  private function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  private function isDuplicateEmail($email) {
    return User::where('email', $email)->exists();
  }

  private function createUser($data, $roleId) {
    $user = User::create([
      'name'     => $data['name'],
      'email'    => $data['email'],
      'role_id'  => $roleId,
      'password' => bcrypt('default_password'), // Set default password or handle as needed
    ]);

    return $user->id;
  }

  private function prepareStudentData($data, $userId, $institutionId) {
    return [
      'user_id'        => $userId,
      'id_institution' => $institutionId,
      'created_at'     => now(),
      'updated_at'     => now(),
    ];
  }

 

    //   if (!empty($import->errors)) {
    //     return response()->json([
    //       'status' => 'error',
    //       'errors' => $import->errors,
    //     ], 422);
    //   }

    //   return response()->json([
    //     'status'         => 'success',
    //     // 'imported_users' => $import->count,
    //     'message'        => 'CSV file imported successfully',
    //   ], 200);

  }

}
