<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaQuestion extends Model
{
    protected $fillable = [
        'question',
        'answer_a',
        'answer_b',
        'answer_c',
        'answer_d',
        'correct_answer',
        'difficulty',
        'score_value',
        'is_custom',
    ];

    public function getAnswers(): array
    {
        return [
            'a' => $this->answer_a,
            'b' => $this->answer_b,
            'c' => $this->answer_c,
            'd' => $this->answer_d,
        ];
    }
}
