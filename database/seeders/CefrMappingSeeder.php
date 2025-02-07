<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CefrMappingSeeder extends Seeder {
  /**
   * Run the database seeds.
   */
  public function run(): void {
    DB::table('cefr_mappings')->insert([
      ['level' => 'B1', 'language' => 'English', 'lesson' => 40, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'B2', 'language' => 'English', 'lesson' => 40, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'C1', 'language' => 'English', 'lesson' => 40, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'C1+', 'language' => 'English', 'lesson' => 40, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'B1', 'language' => 'French', 'lesson' => 30, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'B2', 'language' => 'French', 'lesson' => 30, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'C1', 'language' => 'French', 'lesson' => 20, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
      ['level' => 'C1+', 'language' => 'French', 'lesson' => 20, 'seuil_heures_jours' => 20, 'noteCC1_ratio' => 60, 'noteCC2_ratio' => 40],
    ]);
  }
}
