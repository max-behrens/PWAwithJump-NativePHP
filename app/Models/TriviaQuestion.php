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
    ];
}