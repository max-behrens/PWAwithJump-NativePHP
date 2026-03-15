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
        'current_question',
        'question_ids',
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    public function rounds()
    {
        return $this->hasMany(TriviaRound::class, 'game_id')->orderBy('round_number');
    }

    public function currentQuestion()
    {
        $ids = $this->question_ids;
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
}
