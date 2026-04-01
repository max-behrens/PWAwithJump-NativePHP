<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\TriviaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('tasks', TaskController::class);

Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [AiController::class, 'index'])->name('index');
    Route::post('/predict', [AiController::class, 'predict'])->name('predict');
    Route::post('/train', [AiController::class, 'train'])->name('train');
    Route::get('/health', [AiController::class, 'health'])->name('health');
});

Route::prefix('trivia')->name('trivia.')->group(function () {
    Route::get('/', [TriviaController::class, 'index'])->name('index');
    Route::get('/stats-json', [TriviaController::class, 'statsJson'])->name('stats-json');
    Route::post('/start', [TriviaController::class, 'start'])->name('start');
    Route::get('/play/{trivia}', [TriviaController::class, 'play'])->name('play');
    Route::post('/play/{trivia}/answer', [TriviaController::class, 'answer'])->name('answer');
    Route::get('/result/{trivia}', [TriviaController::class, 'result'])->name('result');
});

Route::get('/solar', fn() => view('solar'))->name('solar.index');