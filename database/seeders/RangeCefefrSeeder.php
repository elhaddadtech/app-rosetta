<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RangeCefefrSeeder extends Seeder {
  /**
   * Run the database seeds.
   */
  public function run(): void {
    DB::table('roles')->insert([
      ['libelle' => 'etudiant'],
      ['libelle' => 'admin'],
      ['libelle' => 'teacher'],
    ]);
    DB::table('range_cefefrs')->insert([
      ['language' => 'French', 'scaled_score' => 136, 'semester' => 'S1'],
      ['language' => 'French', 'scaled_score' => 172, 'semester' => 'S2'],
      ['language' => 'French', 'scaled_score' => 185, 'semester' => 'S3'],
      ['language' => 'French', 'scaled_score' => 197, 'semester' => 'S4'],
      ['language' => 'French', 'scaled_score' => 219, 'semester' => 'S5'],
      ['language' => 'French', 'scaled_score' => 240, 'semester' => 'S6'],
      ['language' => 'French', 'scaled_score' => 240, 'semester' => 'M1-S1'],
      ['language' => 'French', 'scaled_score' => 259, 'semester' => 'M1-S2'],
      ['language' => 'French', 'scaled_score' => 280, 'semester' => 'M1-S3'],
      // language Ensglish
      ['language' => 'English', 'scaled_score' => 115, 'semester' => 'S1'],
      ['language' => 'English', 'scaled_score' => 155, 'semester' => 'S2'],
      ['language' => 'English', 'scaled_score' => 174, 'semester' => 'S3'],
      ['language' => 'English', 'scaled_score' => 192, 'semester' => 'S4'],
      ['language' => 'English', 'scaled_score' => 209, 'semester' => 'S5'],
      ['language' => 'English', 'scaled_score' => 225, 'semester' => 'S6'],
      ['language' => 'English', 'scaled_score' => 225, 'semester' => 'M1-S1'],
      ['language' => 'English', 'scaled_score' => 245, 'semester' => 'M1-S2'],
      ['language' => 'English', 'scaled_score' => 264, 'semester' => 'M1-S3'],

      //spanish
      ['language' => 'Spansih', 'scaled_score' => 115, 'semester' => 'S1'],
      ['language' => 'Spanish', 'scaled_score' => 155, 'semester' => 'S2'],
      ['language' => 'Spanish', 'scaled_score' => 174, 'semester' => 'S3'],
      ['language' => 'Spanish', 'scaled_score' => 192, 'semester' => 'S4'],
      ['language' => 'Spanish', 'scaled_score' => 209, 'semester' => 'S5'],
      ['language' => 'Spanish', 'scaled_score' => 225, 'semester' => 'S6'],
      ['language' => 'Spanish', 'scaled_score' => 225, 'semester' => 'M1-S1'],
      ['language' => 'Spanish', 'scaled_score' => 245, 'semester' => 'M1-S2'],
      ['language' => 'Spanish', 'scaled_score' => 264, 'semester' => 'M1-S3'],

      //German
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S1'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S2'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S3'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S4'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S5'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'S6'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'M1-S1'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'M1-S2'],
      ['language' => 'German', 'scaled_score' => 0, 'semester' => 'M1-S3'],

    ]);
  }
}
