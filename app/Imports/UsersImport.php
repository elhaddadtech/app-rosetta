<?php

namespace App\Imports;

use App\Models\Institution;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;

class UsersImport implements ToCollection, WithHeadingRow {

  public $errors = [];
  public function collection(Collection $rows) {
    foreach ($rows as $index => $row) {
      //   dd($row->toArray());
      $validator = Validator::make($row->toArray(), [
        'last_name'     => 'required|alpha_num:ascii|string|max:255',
        'first_name'    => 'required|alpha_num:ascii|string|max:255',
        'email'         => 'required|email|unique:users,email',
        'cne'           => 'required|string|unique:students,cne',
        'apogee'        => 'required|numeric|unique:students,apogee',
        'institution'   => 'required|string|max:255',
        'role'          => 'required|string|max:255',
        'date_of_birth' => 'required|string|max:255',
      ]);
      $role    = Role::where('Libelle', strtolower($row['role']))->first();
      $student = Student::where('cne', strtolower($row['cne']))->first();

      if ($validator->fails() || !$role || $student) {
        $errors = $validator->errors()->all();

        if (!$role) {
          $errors[] = "The Role {$row['role']} not found.";
        }

        if (!$student) {
          $errors[] ="The CNE {$row['cne']} is already registered.";
        }

        $this->errors[] = [
          'row' => $index + 1 + 1, // extra +1 for heder row
          'errors' => $errors,

        ];
      }
    }

    if (empty($this->errors)) {
      // Insert to database
      foreach ($rows as $row) {

        $institution = Institution::firstOrCreate(['libelle' => strtolower($row['institution'])]);
        // dd( $institution->id);
        $role = Role::where('Libelle', strtolower($row['role']))->first();

        $user = User::firstOrCreate([
          'email' => strtolower($row['email'])], [
          'first_name' => $row['first_name'],
          'last_name'  => $row['last_name'],
          'role_id'    => $role->id,
        ]);

        $student = Student::firstOrCreate([
          'cne' =>strtolower( $row['cne']),
          'apogee'         => $row['apogee'],
          'birthdate'=> $row['date_of_birth'],
          'institution_id' => $institution->id,
          'user_id'        => $user->id,
        ]);
      }

    }

  }

  // protected function removeUsersWithoutStudents()
  // {
  //     // Find all users without corresponding student records
  //     $usersWithoutStudents = User::whereDoesntHave('student')->get();

  //     foreach ($usersWithoutStudents as $user) {
  //         $user->delete();
  //     }
  // }

}
