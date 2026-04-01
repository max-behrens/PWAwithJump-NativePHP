<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('answer_a');
            $table->string('answer_b');
            $table->string('answer_c');
            $table->string('answer_d');
            $table->string('correct_answer'); // 'a','b','c','d'
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->timestamps();
        });

        Schema::create('trivia_games', function (Blueprint $table) {
            $table->id();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->integer('user_score')->default(0);
            $table->integer('ai_score')->default(0);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->integer('current_round')->default(1);    // 1-3
            $table->integer('current_question')->default(0); // 0-2 within round
            $table->json('round_1_question_ids');
            $table->json('round_2_question_ids');
            $table->json('round_3_question_ids');
            $table->timestamps();
        });

        Schema::create('trivia_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('trivia_games')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('trivia_questions')->onDelete('cascade');
            $table->integer('game_round');       // 1-3
            $table->integer('question_number');  // 1-3 within round
            $table->string('user_answer')->nullable();
            $table->string('ai_answer')->nullable();
            $table->boolean('user_steal')->default(false);
            $table->boolean('ai_steal')->default(false);
            $table->boolean('user_correct')->nullable();
            $table->boolean('ai_correct')->nullable();
            $table->integer('user_points_earned')->default(0);
            $table->integer('ai_points_earned')->default(0);
            $table->integer('base_score')->default(1);
            $table->string('ai_strategy')->nullable(); // A, B, C, D
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_rounds');
        Schema::dropIfExists('trivia_games');
        Schema::dropIfExists('trivia_questions');
    }
};