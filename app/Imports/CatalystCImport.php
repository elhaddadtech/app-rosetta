<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CatalystCImport implements ToCollection
{
    public function collection(Collection $rows)
    {
      dd($rows);
        $filteredRows = $rows->skip(1)->map(function ($row) {
            return [
                'first_name' => $row[0],
                'last_name' => $row[1],
                'email' => $row[2],
                'time' => $row[3],
                'total' => $row[4],
            ];
        });

        // Remove duplicates based on the email field
        $uniqueRows = $filteredRows->unique('email');
        dd("Email");
        // Save data to a local JSON file
        file_put_contents(storage_path('app/public/filtered_data.json'), $uniqueRows->toJson());
    }
}
