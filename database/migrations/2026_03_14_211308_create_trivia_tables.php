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
            $table->string('correct_answer'); // 'a', 'b', 'c', or 'd'
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'custom'])->default('medium');
            $table->integer('score_value')->default(1); // easy=1, medium=2, hard=3, custom=user defined
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });

        Schema::create('trivia_games', function (Blueprint $table) {
            $table->id();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'custom']);
            $table->integer('user_score')->default(0);
            $table->integer('ai_score')->default(0);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->integer('current_question')->default(0); // 0-4
            $table->json('question_ids'); // array of 5 question ids
            $table->timestamps();
        });

        Schema::create('trivia_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('trivia_games')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('trivia_questions')->onDelete('cascade');
            $table->integer('round_number'); // 1-5
            $table->string('user_answer')->nullable(); // 'a','b','c','d'
            $table->string('ai_answer')->nullable();
            $table->boolean('user_steal')->default(false);
            $table->boolean('ai_steal')->default(false);
            $table->boolean('user_correct')->nullable();
            $table->boolean('ai_correct')->nullable();
            $table->integer('user_points_earned')->default(0);
            $table->integer('ai_points_earned')->default(0);
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