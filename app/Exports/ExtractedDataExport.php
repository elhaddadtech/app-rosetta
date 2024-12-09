<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExtractedDataExport implements FromCollection, WithHeadings
{
    /**
     * Récupérer les données à exporter.
     */
    public function collection()
    {
        // Sélectionnez uniquement les colonnes nécessaires
        return [
          [
              "first_name" => "Abderrahman",
              "last_name" => "Talibi",
              "email" => "a.talibi3125@uca.ac.ma",
          ]
      ];
    }

    /**
     * Ajouter les en-têtes dans le fichier Excel.
     */
    public function headings(): array
    {
        return ['first_name', 'last_name',  'email',];
    }
}

