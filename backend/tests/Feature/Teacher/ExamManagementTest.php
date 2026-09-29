<?php

namespace Tests\Feature\Teacher;

use App\Models\Exam;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamManagementTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::factory()->create();
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return ['X-User-Role' => 'teacher', 'X-User-Id' => (string) $this->teacher->id];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'title' => 'Prova de Teste',
            'description' => 'Descrição',
            'questions' => [
                [
                    'statement' => 'Quanto é 2 + 2?',
                    'options' => [
                        ['text' => '3', 'is_correct' => false],
                        ['text' => '4', 'is_correct' => true],
                    ],
                ],
            ],
        ];
    }

    public function test_sem_header_de_perfil_retorna_403(): void
    {
        $this->getJson('/api/exams')->assertForbidden();
    }

    public function test_perfil_de_aluno_nao_acessa_area_do_professor(): void
    {
        $this->getJson('/api/exams', ['X-User-Role' => 'student', 'X-User-Id' => '1'])
            ->assertForbidden();
    }

    public function test_professor_inexistente_retorna_401(): void
    {
        $this->getJson('/api/exams', ['X-User-Role' => 'teacher', 'X-User-Id' => '9999'])
            ->assertUnauthorized();
    }

    public function test_lista_provas_com_contadores(): void
    {
        Exam::factory()->count(2)->create(['teacher_id' => $this->teacher->id]);

        $this->getJson('/api/exams', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'questions_count', 'attempts_count']]]);
    }

    public function test_cria_prova_com_questoes_e_alternativas(): void
    {
        $this->postJson('/api/exams', $this->validPayload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prova de Teste')
            ->assertJsonCount(1, 'data.questions');

        $this->assertDatabaseHas('exams', ['title' => 'Prova de Teste', 'teacher_id' => $this->teacher->id]);
        $this->assertDatabaseHas('questions', ['statement' => 'Quanto é 2 + 2?']);
        $this->assertDatabaseHas('options', ['text' => '4', 'is_correct' => true]);
    }

    public function test_nao_cria_prova_sem_alternativa_correta(): void
    {
        $payload = $this->validPayload();
        $payload['questions'][0]['options'][1]['is_correct'] = false;

        $this->postJson('/api/exams', $payload, $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('questions.0.options');
    }

    public function test_nao_cria_prova_com_duas_alternativas_corretas(): void
    {
        $payload = $this->validPayload();
        $payload['questions'][0]['options'][0]['is_correct'] = true;

        $this->postJson('/api/exams', $payload, $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('questions.0.options');
    }

    public function test_nao_cria_prova_sem_titulo(): void
    {
        $payload = $this->validPayload();
        unset($payload['title']);

        $this->postJson('/api/exams', $payload, $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_mostra_prova_com_gabarito(): void
    {
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
        $question = $exam->questions()->create(['statement' => 'Q?', 'order' => 1]);
        $question->options()->createMany([
            ['text' => 'A', 'is_correct' => true, 'order' => 1],
            ['text' => 'B', 'is_correct' => false, 'order' => 2],
        ]);

        $this->getJson("/api/exams/{$exam->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.questions.0.options.0.is_correct', true);
    }

    public function test_atualiza_prova_substituindo_questoes(): void
    {
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
        $old = $exam->questions()->create(['statement' => 'Antiga', 'order' => 1]);
        $old->options()->createMany([
            ['text' => 'A', 'is_correct' => true, 'order' => 1],
            ['text' => 'B', 'is_correct' => false, 'order' => 2],
        ]);

        $this->putJson("/api/exams/{$exam->id}", $this->validPayload(), $this->headers())
            ->assertOk()
            ->assertJsonPath('data.title', 'Prova de Teste');

        $this->assertDatabaseMissing('questions', ['statement' => 'Antiga']);
        $this->assertDatabaseHas('questions', ['statement' => 'Quanto é 2 + 2?']);
    }

    public function test_exclui_prova(): void
    {
        $exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->deleteJson("/api/exams/{$exam->id}", [], $this->headers())
            ->assertNoContent();

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }
}
