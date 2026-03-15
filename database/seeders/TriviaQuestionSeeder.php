<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TriviaQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // EASY (score_value = 1)
            [
                'question' => 'What is the capital of France?',
                'answer_a' => 'London',
                'answer_b' => 'Berlin',
                'answer_c' => 'Paris',
                'answer_d' => 'Madrid',
                'correct_answer' => 'c',
                'difficulty' => 'easy',
                'score_value' => 1,
                'is_custom' => false,
            ],
            [
                'question' => 'How many days are in a week?',
                'answer_a' => '5',
                'answer_b' => '6',
                'answer_c' => '8',
                'answer_d' => '7',
                'correct_answer' => 'd',
                'difficulty' => 'easy',
                'score_value' => 1,
                'is_custom' => false,
            ],
            [
                'question' => 'What colour do you get when you mix red and white?',
                'answer_a' => 'Orange',
                'answer_b' => 'Pink',
                'answer_c' => 'Purple',
                'answer_d' => 'Yellow',
                'correct_answer' => 'b',
                'difficulty' => 'easy',
                'score_value' => 1,
                'is_custom' => false,
            ],
            [
                'question' => 'What is 12 x 12?',
                'answer_a' => '124',
                'answer_b' => '136',
                'answer_c' => '144',
                'answer_d' => '148',
                'correct_answer' => 'c',
                'difficulty' => 'easy',
                'score_value' => 1,
                'is_custom' => false,
            ],
            [
                'question' => 'Which planet is known as the Red Planet?',
                'answer_a' => 'Venus',
                'answer_b' => 'Jupiter',
                'answer_c' => 'Saturn',
                'answer_d' => 'Mars',
                'correct_answer' => 'd',
                'difficulty' => 'easy',
                'score_value' => 1,
                'is_custom' => false,
            ],

            // MEDIUM (score_value = 2)
            [
                'question' => 'What year did World War II end?',
                'answer_a' => '1943',
                'answer_b' => '1944',
                'answer_c' => '1945',
                'answer_d' => '1946',
                'correct_answer' => 'c',
                'difficulty' => 'medium',
                'score_value' => 2,
                'is_custom' => false,
            ],
            [
                'question' => 'What is the chemical symbol for gold?',
                'answer_a' => 'Go',
                'answer_b' => 'Gd',
                'answer_c' => 'Ag',
                'answer_d' => 'Au',
                'correct_answer' => 'd',
                'difficulty' => 'medium',
                'score_value' => 2,
                'is_custom' => false,
            ],
            [
                'question' => 'How many bones are in the adult human body?',
                'answer_a' => '196',
                'answer_b' => '206',
                'answer_c' => '216',
                'answer_d' => '226',
                'correct_answer' => 'b',
                'difficulty' => 'medium',
                'score_value' => 2,
                'is_custom' => false,
            ],
            [
                'question' => 'Which country invented pizza?',
                'answer_a' => 'Greece',
                'answer_b' => 'Spain',
                'answer_c' => 'Italy',
                'answer_d' => 'France',
                'correct_answer' => 'c',
                'difficulty' => 'medium',
                'score_value' => 2,
                'is_custom' => false,
            ],
            [
                'question' => 'What is the largest ocean on Earth?',
                'answer_a' => 'Atlantic',
                'answer_b' => 'Indian',
                'answer_c' => 'Arctic',
                'answer_d' => 'Pacific',
                'correct_answer' => 'd',
                'difficulty' => 'medium',
                'score_value' => 2,
                'is_custom' => false,
            ],

            // HARD (score_value = 3)
            [
                'question' => 'What is the speed of light in metres per second?',
                'answer_a' => '299,792,458',
                'answer_b' => '199,792,458',
                'answer_c' => '399,792,458',
                'answer_d' => '249,792,458',
                'correct_answer' => 'a',
                'difficulty' => 'hard',
                'score_value' => 3,
                'is_custom' => false,
            ],
            [
                'question' => 'In what year was the Magna Carta signed?',
                'answer_a' => '1066',
                'answer_b' => '1215',
                'answer_c' => '1314',
                'answer_d' => '1189',
                'correct_answer' => 'b',
                'difficulty' => 'hard',
                'score_value' => 3,
                'is_custom' => false,
            ],
            [
                'question' => 'What is the hardest natural substance on Earth?',
                'answer_a' => 'Titanium',
                'answer_b' => 'Quartz',
                'answer_c' => 'Diamond',
                'answer_d' => 'Graphene',
                'correct_answer' => 'c',
                'difficulty' => 'hard',
                'score_value' => 3,
                'is_custom' => false,
            ],
            [
                'question' => 'Which element has the atomic number 79?',
                'answer_a' => 'Silver',
                'answer_b' => 'Platinum',
                'answer_c' => 'Mercury',
                'answer_d' => 'Gold',
                'correct_answer' => 'd',
                'difficulty' => 'hard',
                'score_value' => 3,
                'is_custom' => false,
            ],
            [
                'question' => 'What is the longest river in the world?',
                'answer_a' => 'Amazon',
                'answer_b' => 'Yangtze',
                'answer_c' => 'Nile',
                'answer_d' => 'Mississippi',
                'correct_answer' => 'c',
                'difficulty' => 'hard',
                'score_value' => 3,
                'is_custom' => false,
            ],
        ];

        foreach ($questions as $q) {
            DB::table('trivia_questions')->insert(array_merge($q, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
