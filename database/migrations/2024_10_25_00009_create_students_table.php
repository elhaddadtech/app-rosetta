<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('students', function (Blueprint $table) {
      $table->id();
      $table->string('cne', 45);
      $table->string('apogee', 45);
      $table->string('birthdate')->nullable(); // Date de naissance
      $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
      $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
      $table->foreignId('semester_id')->nullable()->constrained()->onDelete('cascade');
      $table->foreignId('institution_id')->nullable()->constrained()->onDelete('cascade');
      $table->foreignId('language_id')->nullable()->constrained()->onDelete('cascade');
      $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
      $table->date('first_access')->nullable();
      $table->date('last_access')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('students');
  }
};
