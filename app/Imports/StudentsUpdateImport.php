<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Role;
use App\Models\User;
use App\Models\Student;
use App\Models\Institution;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsUpdateImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    use Importable;
    protected $duplicates = [];
    protected $importedCount = 0;
    protected $errors = [];
    protected $updatedCount = 0;

    public function model(array $row)
    {
        $requiredFields = [
            'first_name', 'last_name', 'email', 'cne',
            'apogee', 'institution', 'date_of_birth', 'role'
        ];

        // Check for required fields
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $row) || empty($row[$field])) {
                $this->errors[] = "Empty field: {$field}";
                return null;
            }
        }

        $data = $this->parseRow($row);

        // Check email validity
        if (!$this->isValidEmail($data['email'])) {
            $this->errors[] = "Invalid email '{$data['email']}'";
            return null;
        }

        // Get Role and Institution IDs
        $roleId = $this->getRoleId($data['role']);
        if (is_null($roleId)) {
            $this->errors[] = "Role '{$data['role']}' not found.";
            return null;
        }

        $institutionId = $this->getInstitutionId($data['institution']);

        // Update or create user
        $userId = $this->createOrUpdateUser($data, $roleId);

        // Update or create student
        return $this->createOrUpdateStudent($data, $institutionId, $userId);
    }

    // Check if email is valid
    private function isValidEmail($email)
    {
        return preg_match('/^[a-zA-Z0-9._%+-]+@uca\.ac\.ma$/', $email);
    }

    // Get Role ID
    private function getRoleId($roleName)
    {
        $role = Role::where('Libelle', $roleName)->first();
        return $role ? $role->id : null;
    }

    // Create or update user
    private function createOrUpdateUser($data, $roleId)
    {
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $user->update([
                'firstname' => $data['firstName'],
                'lastname' => $data['lastName'],
                'id_role' => $roleId,
                'updated_at' => now(),
            ]);
            $this->updatedCount++;
        } 

        return $user->id;
    }

    // Create or update student
    private function createOrUpdateStudent($data, $institutionId, $userId)
    {
        $student = Student::where('CNE', $data['cne'])->first();

        if ($student) {
            $student->update([
                'Apogee' => $data['apogee'],
                'date_naissance' => $data['birth'],
                'id_group' => $data['group'],
                'id_branch' => $data['branch'],
                'id_semester' => $data['semester'],
                'id_institution' => $institutionId,
                'id_user' => $userId,
                'updated_at' => now(),
            ]);
            $this->updatedCount++;
        } 
    }

    // Get Institution ID or create it
    private function getInstitutionId($institutionName)
    {
        $institution = Institution::where('Libelle', $institutionName)->first();
        return $institution->update([
            'Libelle' => $institutionName,
            'created_at' => now(),
            'updated_at' => now(),
        ])->id;
    }

    // Format date for MySQL
    private function formatDate($date)
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->errors[] = "Row skipped: Invalid date format '{$date}'";
            return null;
        }
    }

    // Parse row data
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

    // Retrieve import count
    public function getImportCount()
    {
        return $this->importedCount;
    }

    // Retrieve update count
    public function getUpdateCount()
    {
        return $this->updatedCount;
    }

    // Retrieve errors
    public function getErrors()
    {
        return $this->errors;
    }

}