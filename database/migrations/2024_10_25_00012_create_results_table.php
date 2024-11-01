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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->string('Test_1', 45); 
            $table->string('Score_Test_1', 45); 
            $table->string('Time_PC', 45);
            $table->string('Time_Mobile', 45); 
            $table->string('Time_All', 45); 
            $table->string('Activity', 45); 
            $table->string('Pass_Score', 45); 
            $table->string('Score_All', 45);
            $table->string('Test_2', 45); 
            $table->string('Score_Test_2', 45);
            $table->string('Test_3', 45); 
            $table->string('Score_Test_3', 45); 
            $table->string('Test_4', 45);
            $table->string('Score_Test_4', 45); 
            $table->foreignId('id_student')->constrained('students')->onDelete('cascade');
            $table->foreignId('id_language')->constrained('languages')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
