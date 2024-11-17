<?php
namespace App\Imports;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Student;
use App\Models\Institution;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    use Importable;
    protected $duplicates = [];
    protected $importedCount = 0;
    protected $errors = [];

    public function model(array $row)
    {
        $requiredFields = [
            'first_name', 'last_name', 'email', 'cne', 
            'apogee', 'institution', 'date_of_birth', 'role'
        ];

        // Check for required fields
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $row) || empty($row[$field])) {
                $this->errors[] = " empty field: {$field}";
                return null;
            }
        }

        // Check if email is valid and unique
        if (!$this->isValidEmail($row['email'])) {
            $this->errors[] = "duplicate or inalid email '{$row['email']}'";
            return null;
        }

        // Get role and institution IDs
        $roleId = $this->getRoleId($row['role']);
        if (is_null($roleId)) {
            $this->errors[] = "Role '{$row['role']}' not found.";
            return null;
        }

        $institutionId = $this->getInstitutionId($row['institution']);
        $data = $this->parseRow($row);
        $userId = $this->createUser($data, $roleId);

        // if (is_null($userId)) {
        //     $this->errors[] = "Duplicate email '{$row['email']}' found.";
        //     return null;
        // }

        return $this->createStudent($data, $institutionId, $userId);
    }

    // Check if email is valid (matches pattern) and not a duplicate
    private function isValidEmail($email)
    {
        if ( User::where('email', $email)->exists() || !preg_match('/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/', $email)) {
            // $this->errors[] ="invalid email '{$email}'";
            return false;
        }
        return true;
    }

    // Get Role ID from role name
    private function getRoleId($roleName)
    {
        $role = Role::where('Libelle', $roleName)->first();
        return $role ? $role->id : null;
    }

    // Create User and increase import count
    private function createUser($data, $roleId)
    {
        if (!$this->isDuplicateEmail($data['email'])) {
            $user = User::create([
                'firstname' => $data['firstName'],
                'lastname' => $data['lastName'],
                'email' => $data['email'],
                'id_role' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->importedCount++;
            return $user->id;
        }
        $this->duplicates[] = $data['email'];
        return null;
    }

    // Check for duplicate email
    private function isDuplicateEmail($email)
    {
        return User::where('email', $email)->exists();
    }

    // Create Student entry linked to User and Institution
    private function createStudent($data, $institutionId, $userId)
    {
        if (Student::where('CNE', $data['cne'])->exists()) {
            $this->errors[] = "Duplicate CNE '{$data['cne']}' found.";
            return null;
        }
        return Student::create([
            'CNE' => $data['cne'],
            'Apogee' => $data['apogee'],
            'date_naissance' => $data['birth'],
            'id_group' => $data['group'],
            'id_branch' => $data['branch'],
            'id_semester' => $data['semester'],
            'id_institution' => $institutionId,
            'id_language' => null,
            'id_user' => $userId,
            'First_Access' => null,
            'Last_Access' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Get Institution ID or create new if not exists
    private function getInstitutionId($institutionName)
    {
        $institution = Institution::where('Libelle', $institutionName)->first();
        return $institution ? $institution->id : Institution::create([
            'Libelle' => $institutionName,
            'created_at' => now(),
            'updated_at' => now(),
        ])->id;
    }

    // Format date for MySQL (from m/d/Y to Y-m-d format)
    private function formatDate($date)
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->errors[] = "Row skipped: Invalid date format '{$date}'";
            return null;
        }
    }

    // Parse row data into expected format
    private function parseRow(array $row)
    {
        return [
            'firstName' => $row['first_name'] ?? null,
            'lastName' => $row['last_name'] ?? null,
            'email' => $row['email'] ?? null,
            'cne' => $row['cne'] ?? null,
            'apogee' => $row['apogee'] ?? null,
            'institution' => $row['institution'] ?? null,
            'group' => $row['group'] ?? null,
            'branch' => $row['branch'] ?? null,
            'semester' => $row['semester'] ?? null,
            'birth' => $this->formatDate($row['date_of_birth']),
            'role' => $row['role'] ?? null,
        ];
    }

    // Retrieve duplicates
    public function getDuplicates()
    {
        return $this->duplicates;
    }

    // Retrieve import count
    public function getImportCount()
    {
        return $this->importedCount;
    }

    // Retrieve all errors
    public function getErrors()
    {
        return $this->errors;
    }
}