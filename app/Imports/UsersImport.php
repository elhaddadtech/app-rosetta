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

class UsersImport implements ToCollection, WithHeadingRow {

  public $errors = [];
  public $count  = 0;
  public function collection(Collection $rows) {
    $domainName = strtolower(env('DOMAIN_NAME'));
    foreach ($rows as $index => $row) {
      $emailRegex = '/^[a-zA-Z0-9._%+-]+' . preg_quote($domainName, '/') . '$/';
      $validator  = Validator::make($row->toArray(), [
        // 'last_name'     => 'required|string|max:255',
        // 'first_name'    => 'required|string|max:255',
        'email'         => 'required|email|regex:' . $emailRegex,
        'cne'           => 'required',
        'institution'   => 'required|string|max:255',
        'role'          => 'required|string|max:255',
        'date_of_birth' => 'required|string|max:255',

      ], ['email.regex' => "Email {$row['email']} must be from the domain {$domainName}."]);
      // dd($validator->errors()->all());
      $role = Role::where('Libelle', strtolower($row['role']))->first();
      // $student = Student::where('cne', strtolower($row['cne']))->first(); || $student

      if ($validator->fails() || !$role) {
        $errors = $validator->errors()->all();

        if (!$role) {
          $errors[] = "The Role {$row['role']} not found.";
        }

        // if (!preg_match('/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/', $row['email'])) {
        //   $errors[] = "The Email {$row['email']} is invalid.";
        // }

        $this->errors[] = [
          'row'    => $index + 1 + 1, // extra +1 for heder row
          'errors' => $errors,

        ];
      }
    }

    if (empty($this->errors)) {
      // Insert to database
      foreach ($rows as $row) {
        $institution = Institution::updateOrCreate(['libelle' => strtolower($row['institution'])]);
        $group       = strtolower($row['group']) != '' ? Group::updateOrCreate(['libelle' => strtolower($row['group'])])->id : null;
        $branch      = strtolower($row['branch']) != '' ? Branche::updateOrCreate(['libelle' => strtolower($row['branch'])])->id : null;
        $semester    = strtolower($row['semester']) != '' ? Semester::updateOrCreate(['libelle' => strtolower($row['semester'])])->id : null;
        // dd( $institution->id);
        $role = Role::where('Libelle', strtolower($row['role']))->first();

        $user = User::updateOrCreate([
          'email' => strtolower($row['email'])], [
          'first_name' => $row['first_name'],
          'last_name'  => $row['last_name'],
          'role_id'    => $role->id,
        ]);

        $student = Student::updateOrCreate(
          [
            'user_id' => $user->id,
          ], [
            'cne'            => strtolower($row['cne']),
            'apogee'         => $row['apogee'],
            'birthdate'      => $row['date_of_birth'],
            'group_id'       => $group,
            'branch_id'      => $branch,
            'semester_id'    => $semester,
            'institution_id' => $institution->id,

          ]);
        $this->count += 1;

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
