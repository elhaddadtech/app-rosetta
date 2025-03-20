<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('courses', function (Blueprint $table) {
      $table->id(); // Primary key
      $table->string('cours_name')->nullable(); // Course Name
      $table->string('cours_progress')->nullable(); // Course Progress
      $table->string('cours_grade')->nullable(); // Course Grade (nullable if no grade)
      $table->integer('total_lessons')->nullable(); // Total Lessons
      $table->string('noteCC1', 20)->default(0); // Note for CC1  (nullable if no note)
      $table->string('noteCC2', 20)->default(0); // Note for CC2 (nullable if no note)
      $table->string('noteCC', 20)->default(0); // Note for CC (nullable if no note)
      $table->string('note_ceef', 20)->nullable(); // Note for CC (nullable if no note)

      // Foreign key to the results table
      $table->unsignedBigInteger('result_id'); // Foreign key column
      $table->foreign('result_id')
            ->references('id')
            ->on('results')
            ->onDelete('cascade'); // If the result is deleted, the course is deleted
      $table->string('file', 45)->nullable(); // For file tracking

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('courses');
  }
};
