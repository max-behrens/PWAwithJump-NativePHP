<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TriviaQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [

            // ─── EASY (9 questions) ──────────────────────────────────────────
            ['question' => 'What is the capital of France?',
             'answer_a' => 'London', 'answer_b' => 'Berlin', 'answer_c' => 'Paris', 'answer_d' => 'Madrid',
             'correct_answer' => 'c', 'difficulty' => 'easy'],

            ['question' => 'How many days are in a week?',
             'answer_a' => '5', 'answer_b' => '6', 'answer_c' => '8', 'answer_d' => '7',
             'correct_answer' => 'd', 'difficulty' => 'easy'],

            ['question' => 'What colour do you get mixing red and white?',
             'answer_a' => 'Orange', 'answer_b' => 'Pink', 'answer_c' => 'Purple', 'answer_d' => 'Yellow',
             'correct_answer' => 'b', 'difficulty' => 'easy'],

            ['question' => 'Which planet is known as the Red Planet?',
             'answer_a' => 'Venus', 'answer_b' => 'Jupiter', 'answer_c' => 'Saturn', 'answer_d' => 'Mars',
             'correct_answer' => 'd', 'difficulty' => 'easy'],

            ['question' => 'How many sides does a triangle have?',
             'answer_a' => '2', 'answer_b' => '4', 'answer_c' => '3', 'answer_d' => '5',
             'correct_answer' => 'c', 'difficulty' => 'easy'],

            ['question' => 'What is the largest animal on Earth?',
             'answer_a' => 'Elephant', 'answer_b' => 'Blue whale', 'answer_c' => 'Giraffe', 'answer_d' => 'Great white shark',
             'correct_answer' => 'b', 'difficulty' => 'easy'],

            ['question' => 'What is the boiling point of water in Celsius?',
             'answer_a' => '90', 'answer_b' => '110', 'answer_c' => '100', 'answer_d' => '80',
             'correct_answer' => 'c', 'difficulty' => 'easy'],

            ['question' => 'Which country is home to the kangaroo?',
             'answer_a' => 'New Zealand', 'answer_b' => 'South Africa', 'answer_c' => 'Brazil', 'answer_d' => 'Australia',
             'correct_answer' => 'd', 'difficulty' => 'easy'],

            ['question' => 'What is the capital of Japan?',
             'answer_a' => 'Beijing', 'answer_b' => 'Seoul', 'answer_c' => 'Tokyo', 'answer_d' => 'Bangkok',
             'correct_answer' => 'c', 'difficulty' => 'easy'],

            // ─── MEDIUM (9 questions) ────────────────────────────────────────
            ['question' => 'What year did World War II end?',
             'answer_a' => '1943', 'answer_b' => '1944', 'answer_c' => '1945', 'answer_d' => '1946',
             'correct_answer' => 'c', 'difficulty' => 'medium'],

            ['question' => 'What is the chemical symbol for gold?',
             'answer_a' => 'Go', 'answer_b' => 'Gd', 'answer_c' => 'Ag', 'answer_d' => 'Au',
             'correct_answer' => 'd', 'difficulty' => 'medium'],

            ['question' => 'How many bones are in the adult human body?',
             'answer_a' => '196', 'answer_b' => '206', 'answer_c' => '216', 'answer_d' => '226',
             'correct_answer' => 'b', 'difficulty' => 'medium'],

            ['question' => 'Which country invented pizza?',
             'answer_a' => 'Greece', 'answer_b' => 'Spain', 'answer_c' => 'Italy', 'answer_d' => 'France',
             'correct_answer' => 'c', 'difficulty' => 'medium'],

            ['question' => 'What is the largest organ in the human body?',
             'answer_a' => 'Liver', 'answer_b' => 'Brain', 'answer_c' => 'Lungs', 'answer_d' => 'Skin',
             'correct_answer' => 'd', 'difficulty' => 'medium'],

            ['question' => 'In which year did the Titanic sink?',
             'answer_a' => '1910', 'answer_b' => '1914', 'answer_c' => '1912', 'answer_d' => '1908',
             'correct_answer' => 'c', 'difficulty' => 'medium'],

            ['question' => 'Who painted the Mona Lisa?',
             'answer_a' => 'Michelangelo', 'answer_b' => 'Raphael', 'answer_c' => 'Leonardo da Vinci', 'answer_d' => 'Caravaggio',
             'correct_answer' => 'c', 'difficulty' => 'medium'],

            ['question' => 'What is the tallest mountain in the world?',
             'answer_a' => 'K2', 'answer_b' => 'Kangchenjunga', 'answer_c' => 'Lhotse', 'answer_d' => 'Everest',
             'correct_answer' => 'd', 'difficulty' => 'medium'],

            ['question' => 'What is the capital of Australia?',
             'answer_a' => 'Sydney', 'answer_b' => 'Melbourne', 'answer_c' => 'Canberra', 'answer_d' => 'Brisbane',
             'correct_answer' => 'c', 'difficulty' => 'medium'],

            // ─── HARD (9 questions) ──────────────────────────────────────────
            ['question' => 'What is the speed of light in metres per second?',
             'answer_a' => '299,792,458', 'answer_b' => '199,792,458', 'answer_c' => '399,792,458', 'answer_d' => '249,792,458',
             'correct_answer' => 'a', 'difficulty' => 'hard'],

            ['question' => 'In what year was the Magna Carta signed?',
             'answer_a' => '1066', 'answer_b' => '1215', 'answer_c' => '1314', 'answer_d' => '1189',
             'correct_answer' => 'b', 'difficulty' => 'hard'],

            ['question' => 'Which element has the atomic number 79?',
             'answer_a' => 'Silver', 'answer_b' => 'Platinum', 'answer_c' => 'Mercury', 'answer_d' => 'Gold',
             'correct_answer' => 'd', 'difficulty' => 'hard'],

            ['question' => 'What is the most abundant gas in Earth\'s atmosphere?',
             'answer_a' => 'Oxygen', 'answer_b' => 'Carbon dioxide', 'answer_c' => 'Argon', 'answer_d' => 'Nitrogen',
             'correct_answer' => 'd', 'difficulty' => 'hard'],

            ['question' => 'In which year did the Berlin Wall fall?',
             'answer_a' => '1987', 'answer_b' => '1991', 'answer_c' => '1989', 'answer_d' => '1985',
             'correct_answer' => 'c', 'difficulty' => 'hard'],

            ['question' => 'What is the smallest country in the world by area?',
             'answer_a' => 'Monaco', 'answer_b' => 'San Marino', 'answer_c' => 'Liechtenstein', 'answer_d' => 'Vatican City',
             'correct_answer' => 'd', 'difficulty' => 'hard'],

            ['question' => 'What is the chemical symbol for tungsten?',
             'answer_a' => 'Tu', 'answer_b' => 'Tn', 'answer_c' => 'Tg', 'answer_d' => 'W',
             'correct_answer' => 'd', 'difficulty' => 'hard'],

            ['question' => 'How many chromosomes do humans have?',
             'answer_a' => '44', 'answer_b' => '48', 'answer_c' => '46', 'answer_d' => '42',
             'correct_answer' => 'c', 'difficulty' => 'hard'],

            ['question' => 'What year was the first iPhone released?',
             'answer_a' => '2005', 'answer_b' => '2008', 'answer_c' => '2007', 'answer_d' => '2006',
             'correct_answer' => 'c', 'difficulty' => 'hard'],
        ];

        foreach ($questions as $q) {
            DB::table('trivia_questions')->insert(array_merge($q, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}