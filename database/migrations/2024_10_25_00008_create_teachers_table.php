<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('teachers', function (Blueprint $table) {
      $table->id(); // Primary key
      $table->enum('status', ['vac', 'Permanent']); // Status of the teacher
      // Foreign keys
      $table->foreignId('group_id')->constrained()->onDelete('cascade'); // Reference to groups table
      $table->foreignId('branch_id')->constrained()->onDelete('cascade'); // Reference to branches table
      $table->foreignId('institution_id')->constrained()->onDelete('cascade'); // Reference to institutions table
      $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Reference to users table

      $table->timestamps(); // Created at and updated at timestamps
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('teachers');
  }
};
