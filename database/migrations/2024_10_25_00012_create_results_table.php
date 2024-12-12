<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */

  public function up(): void {
    Schema::create('results', function (Blueprint $table) {
      $table->id(); // Primary key
      $table->string('type_test_1', 45);
      $table->string('date_test_1', 45);
      $table->string('score_test_1', 45);
      $table->string('level_test_1', 45);
      $table->string('test_time_1', 45);
      $table->string('desktop_time', 45);
      $table->string('mobile_time', 45);
      $table->string('test_Time', 45);
      $table->string('total_time', 45);
      $table->string('type_test_2', 45)->nullable();
      $table->string('date_test_2', 45)->nullable();
      $table->string('score_test_2', 45)->nullable();
      $table->string('level_test_2', 45)->nullable();
      $table->string('type_test_3', 45)->nullable();
      $table->string('date_test_3', 45)->nullable();
      $table->string('score_test_3', 45)->nullable();
      $table->string('level_test_3', 45)->nullable();
      $table->string('activity', 45)->nullable();
      $table->string('pass_score', 45)->nullable();
      $table->string('total_score', 45)->nullable();

      // Foreign keys
      $table->foreignId('student_id')->constrained()->onDelete('cascade');
      $table->foreignId('language_id')->constrained()->onDelete('cascade');

      $table->string('file', 45)->nullable(); // For file tracking
      $table->timestamps(); // created_at and updated_at
      $table->softDeletes(); // deleted_at for soft deletes
    });

  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('results');
  }
};
