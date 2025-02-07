<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCefrMappingsTable extends Migration {
  public function up() {
    Schema::create('cefr_mappings', function (Blueprint $table) {
      $table->id(); // Primary Key
      $table->string('level', 10); // CEFR Level (B1, B2, C1, C1+)
      $table->string('language', 20); // Language of Study (English/French)
      $table->integer('lesson'); // Number of lessons
      $table->integer('seuil_heures_jours', )->default(15); // default 15 hours to days
      $table->integer('noteCC1_ratio')->default(60); // 60% Weight (stored as integer)
      $table->integer('noteCC2_ratio')->default(40); // 40% Weight (stored as integer)
      $table->timestamps();
    });
  }

  public function down() {
    Schema::dropIfExists('cefr_mappings');
  }
}
