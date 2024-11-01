<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('do_test', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_result'); // Clé étrangère vers la table Results
            $table->unsignedBigInteger('id_student'); // Clé étrangère vers la table Student
            // Définition des relations (foreign keys)
            $table->foreign('id_result')->references('id')->on('results')->onDelete('cascade');
            $table->foreign('id_student')->references('id')->on('students')->onDelete('cascade');
            $table->string('year', 45); // Colonne 'Year', de type VARCHAR(45)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('do_test');
    }
};
