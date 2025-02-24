<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('range_cefefrs', function (Blueprint $table) {
      $table->id();
      $table->string('language')->nullable();
      $table->integer('scaled_score')->nullable();
      $table->string('semester')->nullable();
      $table->timestamps();
    });

  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('range_cefefrs');
  }
};
