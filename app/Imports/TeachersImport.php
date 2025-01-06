<?php
namespace App\Imports;

use App\Models\Branche;
use App\Models\Group;
use App\Models\Institution;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;

class TeachersImport implements ToCollection, WithHeadingRow {
  public $errors = [];
  public $count  = 0;

  public function collection(Collection $rows) {
    $domainName = strtolower(env('DOMAIN_NAME'));
    // Cache valid values for faster lookups
    $validStatuses = ['vac', 'permanent'];
    // $validRoles    = ['mentor', 'prof', 'all'];

    $existingInstitutions = Institution::pluck('id', 'libelle')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
    $existingGroups       = Group::pluck('id', 'libelle')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
    $existingBranches     = Branche::pluck('id', 'libelle')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
    $existingUsers        = User::pluck('id', 'email')->mapWithKeys(fn($id, $email) => [strtolower($email) => $id]);

    foreach ($rows as $index => $row) {
      $rowIndex = $index + 2; // Row number considering the header

      // Convert row values to lowercase for consistency
      $row        = $row->map(fn($value) => is_string($value) ? strtolower(trim($value)) : $value);
      $emailRegex = '/^[a-zA-Z0-9._%+-]+@' . preg_quote($domainName, '/') . '$/';
      // Validation rules
      $validator = Validator::make($row->toArray(), [
        'email'       => 'required|email|regex:' . $emailRegex,
        'institution' => 'required|string|max:255',
        'status'      => 'required|in:' . implode(',', $validStatuses),
        'group'       => 'required|string|max:255',
        'branch'      => 'required|string|max:255',
        // 'role'        => 'required|in:' . implode(',', $validRoles),
      ], [
        'email.regex' => "The email {$row['email']} must be from the domain {$domainName}.",
        'status.in'   => "Invalid status: {$row['status']}. Valid options are: " . implode(', ', $validStatuses),
        // 'role.in'     => "Invalid role: {$row['role']}. Valid options are: " . implode(', ', $validRoles),
      ]);

      if ($validator->fails()) {
        $this->errors[] = [
          'row'    => $rowIndex,
          'errors' => $validator->errors()->all(),
        ];
        continue; // Skip invalid row
      }

      // Check existence of related entities
      $errors = [];
      if (!isset($existingUsers[$row['email']])) {
        $errors[] = "The email {$row['email']} was not found.";
      }
      if (!isset($existingInstitutions[$row['institution']])) {
        $errors[] = "The institution {$row['institution']} was not found.";
      }
      if (!isset($existingGroups[$row['group']])) {
        $errors[] = "The group {$row['group']} was not found.";
      }
      if (!isset($existingBranches[$row['branch']])) {
        $errors[] = "The branch {$row['branch']} was not found.";
      }

      if (!empty($errors)) {
        $this->errors[] = [
          'row'    => $rowIndex,
          'errors' => $errors,
        ];
        continue; // Skip rows with missing references
      }

      // Insert or Update Teacher Record
      Teacher::updateOrCreate(
        ['user_id' => $existingUsers[$row['email']]],
        [
          'institution_id' => $existingInstitutions[$row['institution']],
          'branch_id'      => $existingBranches[$row['branch']],
          'group_id'       => $existingGroups[$row['group']],
          'status'         => $row['status'],
          // 'role_teach'     => $row['role'],
        ]
      );

      $this->count++;
    }
  }

  // Retrieve Errors
  public function getErrors() {
    return $this->errors;
  }
}
