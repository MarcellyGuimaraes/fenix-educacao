<?php

namespace App\Repositories\Contracts;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Collection;

interface ExamRepositoryInterface
{
    /**
     * Lista as provas de um professor com a contagem de questões e tentativas.
     *
     * @return Collection<int, Exam>
     */
    public function forTeacherWithCounts(int $teacherId): Collection;

    /**
     * Lista as provas disponíveis para um aluno, já com a tentativa dele
     * (quando houver) carregada em `studentAttempt`.
     *
     * @return Collection<int, Exam>
     */
    public function availableForStudent(int $studentId): Collection;

    /**
     * Carrega uma prova com suas questões e alternativas.
     */
    public function loadQuestions(Exam $exam): Exam;

    /**
     * Cria uma prova com questões e alternativas aninhadas.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Exam;

    /**
     * Atualiza uma prova, substituindo suas questões e alternativas.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam;

    public function delete(Exam $exam): void;
}
