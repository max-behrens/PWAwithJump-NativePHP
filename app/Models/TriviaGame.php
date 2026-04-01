<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaGame extends Model
{
    protected $fillable = [
        'difficulty',
        'user_score',
        'ai_score',
        'status',
        'current_round',
        'current_question',
        'round_1_question_ids',
        'round_2_question_ids',
        'round_3_question_ids',
    ];

    protected $casts = [
        'round_1_question_ids' => 'array',
        'round_2_question_ids' => 'array',
        'round_3_question_ids' => 'array',
    ];

    public function rounds()
    {
        return $this->hasMany(TriviaRound::class, 'game_id')
            ->orderBy('game_round')
            ->orderBy('question_number');
    }

    public function currentQuestion(): ?TriviaQuestion
    {
        $key = "round_{$this->current_round}_question_ids";
        $ids = $this->$key ?? [];
        if (!isset($ids[$this->current_question])) return null;
        return TriviaQuestion::find($ids[$this->current_question]);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function winner(): string
    {
        if ($this->user_score > $this->ai_score) return 'user';
        if ($this->ai_score > $this->user_score) return 'ai';
        return 'draw';
    }

    /**
     * Base score for a correct answer this round.
     * Formula: difficulty_base + (round_number - 1)
     * Easy:   R1=1, R2=2, R3=3
     * Medium: R1=2, R2=3, R3=4
     * Hard:   R1=3, R2=4, R3=5
     */
    public function baseScore(): int
    {
        $base = ['easy' => 1, 'medium' => 2, 'hard' => 3][$this->difficulty] ?? 1;
        return $base + ($this->current_round - 1);
    }
}