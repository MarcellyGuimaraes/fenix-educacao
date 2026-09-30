<?php

namespace Tests\Unit;

use App\Http\Requests\ExamRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Regras de validação da prova testadas isoladamente, sem HTTP e sem banco:
 * aplica as regras e o `withValidator` do FormRequest direto sobre o payload.
 */
class ExamRequestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data): ValidatorContract
    {
        $request = ExamRequest::create('/api/exams', 'POST', $data);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);
        $validator->passes();

        return $validator;
    }

    /**
     * @param  array<int, bool>  $correctFlags  `is_correct` de cada alternativa
     * @return array{statement: string, options: array<int, array{text: string, is_correct: bool}>}
     */
    private function question(array $correctFlags): array
    {
        return [
            'statement' => 'Quanto é 2 + 2?',
            'options' => array_map(
                fn (bool $correct, int $i): array => ['text' => "Alternativa {$i}", 'is_correct' => $correct],
                $correctFlags,
                array_keys($correctFlags),
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    private function payload(array $questions): array
    {
        return ['title' => 'Prova', 'questions' => $questions];
    }

    public function test_aceita_questao_com_exatamente_uma_alternativa_correta(): void
    {
        $validator = $this->validate($this->payload([$this->question([true, false, false])]));

        $this->assertFalse($validator->errors()->any());
    }

    public function test_rejeita_questao_sem_alternativa_correta(): void
    {
        $validator = $this->validate($this->payload([$this->question([false, false])]));

        $this->assertSame(
            ['Cada questão deve ter exatamente uma alternativa correta.'],
            $validator->errors()->get('questions.0.options'),
        );
    }

    public function test_rejeita_questao_com_duas_alternativas_corretas(): void
    {
        $validator = $this->validate($this->payload([$this->question([true, true, false])]));

        $this->assertTrue($validator->errors()->has('questions.0.options'));
    }

    public function test_erro_aponta_para_a_questao_invalida(): void
    {
        $validator = $this->validate($this->payload([
            $this->question([true, false]),
            $this->question([false, false]),
        ]));

        $this->assertFalse($validator->errors()->has('questions.0.options'));
        $this->assertTrue($validator->errors()->has('questions.1.options'));
    }

    public function test_exige_titulo_e_ao_menos_uma_questao(): void
    {
        $validator = $this->validate(['questions' => []]);

        $this->assertTrue($validator->errors()->has('title'));
        $this->assertSame(['A prova deve ter ao menos uma questão.'], $validator->errors()->get('questions'));
    }

    public function test_exige_ao_menos_duas_alternativas(): void
    {
        $validator = $this->validate($this->payload([$this->question([true])]));

        $this->assertContains(
            'Cada questão deve ter ao menos duas alternativas.',
            $validator->errors()->get('questions.0.options'),
        );
    }
}
