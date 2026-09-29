<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com dados base: professor, alunos e provas de exemplo.
     */
    public function run(): void
    {
        $teacher = Teacher::firstOrCreate(['name' => 'Prof. Ana Souza']);

        foreach (['João Silva', 'Maria Oliveira', 'Pedro Santos', 'Carla Lima', 'Lucas Rocha'] as $name) {
            Student::firstOrCreate(['name' => $name]);
        }

        $this->createExam($teacher, 'Prova de Conhecimentos Gerais', 'Avaliação introdutória.', [
            [
                'statement' => 'Qual é a capital do Brasil?',
                'options' => [
                    ['text' => 'São Paulo', 'is_correct' => false],
                    ['text' => 'Brasília', 'is_correct' => true],
                    ['text' => 'Rio de Janeiro', 'is_correct' => false],
                    ['text' => 'Salvador', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Quanto é 7 x 6?',
                'options' => [
                    ['text' => '36', 'is_correct' => false],
                    ['text' => '42', 'is_correct' => true],
                    ['text' => '48', 'is_correct' => false],
                    ['text' => '40', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Qual planeta é conhecido como planeta vermelho?',
                'options' => [
                    ['text' => 'Vênus', 'is_correct' => false],
                    ['text' => 'Júpiter', 'is_correct' => false],
                    ['text' => 'Marte', 'is_correct' => true],
                    ['text' => 'Saturno', 'is_correct' => false],
                ],
            ],
        ]);

        $this->createExam($teacher, 'Prova de Lógica de Programação', 'Conceitos básicos.', [
            [
                'statement' => 'O que uma estrutura de repetição (loop) faz?',
                'options' => [
                    ['text' => 'Executa um bloco de código uma única vez', 'is_correct' => false],
                    ['text' => 'Repete um bloco de código enquanto uma condição for verdadeira', 'is_correct' => true],
                    ['text' => 'Declara uma variável', 'is_correct' => false],
                    ['text' => 'Encerra o programa', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Qual estrutura é usada para tomar decisões?',
                'options' => [
                    ['text' => 'if/else', 'is_correct' => true],
                    ['text' => 'for', 'is_correct' => false],
                    ['text' => 'array', 'is_correct' => false],
                    ['text' => 'function', 'is_correct' => false],
                ],
            ],
        ]);
    }

    /**
     * Cria uma prova com suas questões e alternativas.
     *
     * @param  array<int, array{statement: string, options: array<int, array{text: string, is_correct: bool}>}>  $questions
     */
    private function createExam(Teacher $teacher, string $title, string $description, array $questions): void
    {
        $exam = Exam::firstOrCreate(
            ['title' => $title],
            ['teacher_id' => $teacher->id, 'description' => $description],
        );

        // Evita duplicar questões caso o seeder rode mais de uma vez.
        if ($exam->questions()->exists()) {
            return;
        }

        foreach ($questions as $qIndex => $questionData) {
            /** @var Question $question */
            $question = $exam->questions()->create([
                'statement' => $questionData['statement'],
                'order' => $qIndex + 1,
            ]);

            foreach ($questionData['options'] as $oIndex => $optionData) {
                $question->options()->create([
                    'text' => $optionData['text'],
                    'is_correct' => $optionData['is_correct'],
                    'order' => $oIndex + 1,
                ]);
            }
        }
    }
}
