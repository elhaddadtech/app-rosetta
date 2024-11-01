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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['vac', 'Permanent']); 
            $table->enum('role_teach', ['Mentor', 'Prof', 'all']); 
            $table->unsignedBigInteger('id_group'); // Clé étrangère vers la table 'Group'
            $table->unsignedBigInteger('id_branch'); // Clé étrangère vers la table 'Branch'
            $table->unsignedBigInteger('id_institution'); // Clé étrangère vers la table 'Group'
            $table->unsignedBigInteger('id_user');
            // Définition des clés étrangères
            $table->foreign('id_group')->nullable()->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('id_branch')->nullable()->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('id_institution')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
