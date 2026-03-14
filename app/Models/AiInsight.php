<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'task_id',
        'type',
        'input_text',
        'prediction',
        'confidence',
        'completion_likelihood',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}