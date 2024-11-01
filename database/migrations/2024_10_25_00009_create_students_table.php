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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('CNE', 45)->unique();
            $table->string('Apogee', 45);
            $table->date('date_naissance')->nullable();  // Date de naissance
            $table->foreignId('id_group')->nullable()->constrained('groups')->onDelete('cascade');
            $table->foreignId('id_branch')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('id_semester')->nullable()->constrained('semesters')->onDelete('cascade');
            $table->foreignId('id_institution')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('id_language')->nullable()->constrained('languages')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->date('First_Access')->default("");
            $table->date('Last_Access')->default("");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
