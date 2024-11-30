<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CatalystImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
      dd("importing Catalyst");
        $cleanData = collect();
        foreach ($rows as $item) {
          dd($item);
          $student = [
               'name' => $item[0],
               'email' => $item[1],
               'phone' => $item[2],
               'position' => $item[3],
               'department' => $item[4],
               'location' => $item[5],
               'country' => $item[6],
               'created_at' => now(),
               'updated_at' => now(),
          ];
           if(!$cleanData->contains('email',$item[1])){
            $cleanData->push($student);
           }

    }
    session(['students'=> $cleanData]);
    return $cleanData;
  }
}
