<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ExamAttemptService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com dados base: professores, alunos e provas de exemplo.
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

        // Segundo professor, com prova própria: cada professor vê apenas as
        // próprias provas e métricas.
        $otherTeacher = Teacher::firstOrCreate(['name' => 'Prof. Bruno Costa']);

        $this->createExam($otherTeacher, 'Prova de História do Brasil', 'Fatos marcantes.', [
            [
                'statement' => 'Em que ano foi proclamada a independência do Brasil?',
                'options' => [
                    ['text' => '1500', 'is_correct' => false],
                    ['text' => '1822', 'is_correct' => true],
                    ['text' => '1889', 'is_correct' => false],
                    ['text' => '1930', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Qual evento marcou o fim do Império no Brasil?',
                'options' => [
                    ['text' => 'A Proclamação da República', 'is_correct' => true],
                    ['text' => 'A Abolição da Escravatura', 'is_correct' => false],
                    ['text' => 'A Revolução de 1930', 'is_correct' => false],
                    ['text' => 'A chegada da família real', 'is_correct' => false],
                ],
            ],
        ]);

        // Tentativas de exemplo, para o dashboard não abrir vazio. A prova de
        // Conhecimentos Gerais é a "difícil" (média menor): a Maria só fez essa
        // e foi a melhor nela, então aparece acima da média no Aluno × média
        // mesmo sem ter a maior média absoluta.
        $this->createAttempts('Prova de Conhecimentos Gerais', [
            'João Silva' => [true, false, false],
            'Maria Oliveira' => [true, true, false],
            'Pedro Santos' => [false, false, true],
        ]);
        $this->createAttempts('Prova de Lógica de Programação', [
            'João Silva' => [true, true],
            'Lucas Rocha' => [true, true],
            'Carla Lima' => [true, false],
        ]);
        $this->createAttempts('Prova de História do Brasil', [
            'João Silva' => [true, true],
            'Carla Lima' => [true, false],
        ]);
    }

    /**
     * Registra tentativas pela mesma correção usada na API. Alunos que já
     * fizeram a prova são ignorados, para o seeder poder rodar várias vezes.
     *
     * @param  array<string, array<int, bool>>  $answersByStudent  nome do aluno => acertar cada questão?
     */
    private function createAttempts(string $examTitle, array $answersByStudent): void
    {
        $exam = Exam::where('title', $examTitle)->with('questions.options')->firstOrFail();
        $attempts = app(ExamAttemptService::class);

        foreach ($answersByStudent as $studentName => $correctMap) {
            $student = Student::where('name', $studentName)->firstOrFail();

            if ($exam->attempts()->where('student_id', $student->id)->exists()) {
                continue;
            }

            $answers = $exam->questions->values()->map(fn (Question $question, int $index): array => [
                'question_id' => $question->id,
                'option_id' => $question->options->firstWhere('is_correct', $correctMap[$index])->id,
            ])->all();

            $attempts->submit($exam, $student, $answers);
        }
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
