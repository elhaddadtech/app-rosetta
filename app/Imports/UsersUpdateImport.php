<?php

namespace App\Imports;

use App\Models\Branche;
use App\Models\Group;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;

class UsersUpdateImport implements ToCollection, WithHeadingRow {

  public $errors = [];
  public function collection(Collection $rows) {
    set_time_limit(120);

    foreach ($rows as $index => $row) {
      //   dd($row->toArray());
      $validator = Validator::make($row->toArray(), [
        'last_name'     => 'required|alpha_num:ascii|string|max:255',
        'first_name'    => 'required|alpha_num:ascii|string|max:255',
        'email'         => 'required|email',
        'cne'           => 'required|string',
        'apogee'        => 'required|numeric',
        'institution'   => 'required|string|max:255',
        'group'         => 'required|string|',
        'branch'        => 'required|string',
        'semester'      => 'required|string',
        'role'          => 'required|string|max:255',
        'date_of_birth' => 'required|string|max:255',
      ]);
      $role        = Role::where('Libelle', strtolower($row['role']))->first();
      $institution = Institution::where(['libelle' => strtolower($row['institution'])]);
      if ($validator->fails() || !$role || $institution) {
        $errors = $validator->errors()->all();

        if (!$role) {
          $errors[] = "The Role {$row['role']} not found.";
        }
        if (!$institution) {
          $errors[] = "The Institution {$row['institution']} not found.";
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

        $institution = Institution::where(['libelle' => strtolower($row['institution'])]);
        $group       = Group::firstOrCreate(['libelle' => strtolower($row['group'])]);
        $branch      = Branche::firstOrCreate(['libelle' => strtolower($row['branch'])]);
        $semester    = Semester::firstOrCreate(['libelle' => strtolower($row['semester'])]);
        // dd( $institution->id);
        $role = Role::where('Libelle', strtolower($row['role']))->first();
        $user = User::where('email', strtolower($row['email']))->first();
        if ($user) {
          // Update existing user
          $user->update([
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'role_id'    => $role->id,
          ]);
        }
        $student = Student::where('user_id', $user->id)->first();

        if ($student) {
          // Update existing student
          $student->update([
            'cne'            => $row['cne'],
            'apogee'         => $row['apogee'],
            'birthdate'      => $row['date_of_birth'],
            'group_id'       => $group->id,
            'branch_id'      => $branch->id,
            'semester'       => $semester->id,
            'institution_id' => $institution->id,
          ]);
        }

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
