<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaRound extends Model
{
    protected $fillable = [
        'game_id',
        'question_id',
        'round_number',
        'user_answer',
        'ai_answer',
        'user_steal',
        'ai_steal',
        'user_correct',
        'ai_correct',
        'user_points_earned',
        'ai_points_earned',
    ];

    protected $casts = [
        'user_steal' => 'boolean',
        'ai_steal' => 'boolean',
        'user_correct' => 'boolean',
        'ai_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(TriviaQuestion::class, 'question_id');
    }

    public function game()
    {
        return $this->belongsTo(TriviaGame::class, 'game_id');
    }
}
