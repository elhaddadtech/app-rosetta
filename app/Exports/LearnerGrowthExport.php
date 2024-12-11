<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;

class LearnerGrowthExport implements FromArray
{
    /**
     * @return array
     */
    public function array(): array
    {
        $allResults = [];
        $page = 1;
        $perPage = 100;

        do {
            $offset = ($page - 1) * $perPage;

            $paginatedResults = DB::table('results')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $allResults = array_merge($allResults, $paginatedResults->map(fn($item) => (array) $item)->toArray());
          dd($paginatedResults[0]);
            $page++;
        } while (count($paginatedResults) > 0);

        return $allResults;
    }
}
