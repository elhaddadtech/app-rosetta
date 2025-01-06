<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    // Add index to 'language_id' column in 'results' table
    Schema::table('results', function (Blueprint $table) {
      $table->index('language_id');
    });

    // Add index to 'student_id' column in 'results' table
    Schema::table('results', function (Blueprint $table) {
      $table->index('student_id');
    });

    // Add index to 'user_id' column in 'students' table
    Schema::table('students', function (Blueprint $table) {
      $table->index('user_id');
    });

    // Add index to 'institution_id' column in 'students' table
    Schema::table('students', function (Blueprint $table) {
      $table->index('institution_id');
    });

    // Add index to 'result_id' column in 'courses' table
    Schema::table('courses', function (Blueprint $table) {
      $table->index('result_id');
    });

    // Add index to 'total_lessons' column in 'courses' table (for sorting)
    Schema::table('courses', function (Blueprint $table) {
      $table->index('total_lessons');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::table('results', function (Blueprint $table) {
      $table->dropIndex(['language_id']);
      $table->dropIndex(['student_id']);
    });

    Schema::table('students', function (Blueprint $table) {
      $table->dropIndex(['user_id']);
      $table->dropIndex(['institution_id']);
    });

    Schema::table('courses', function (Blueprint $table) {
      $table->dropIndex(['result_id']);
      $table->dropIndex(['total_lessons']);
    });

  }
};
