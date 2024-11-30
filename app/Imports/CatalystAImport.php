<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class CatalystAImport implements ToArray {

  public function array(array $array) {
    // dd($collection);
    return ['test' => 'catalog'];
  }
}
