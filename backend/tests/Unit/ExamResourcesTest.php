<?php

namespace Tests\Unit;

use App\Http\Resources\ExamResource;
use App\Http\Resources\Student\StudentExamResource;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * A mesma prova serializada nas duas visões: o professor recebe o gabarito,
 * o aluno nunca. Modelos montados em memória, sem banco.
 */
class ExamResourcesTest extends TestCase
{
    private function exam(): Exam
    {
        $question = new Question(['statement' => 'Quanto é 2 + 2?', 'order' => 1]);
        $question->id = 10;
        $question->setRelation('options', new Collection([
            tap(new Option(['text' => '4', 'is_correct' => true, 'order' => 1]), fn (Option $o) => $o->id = 100),
            tap(new Option(['text' => '5', 'is_correct' => false, 'order' => 2]), fn (Option $o) => $o->id = 101),
        ]));

        $exam = new Exam(['title' => 'Prova', 'description' => null]);
        $exam->id = 1;
        $exam->setRelation('questions', new Collection([$question]));

        return $exam;
    }

    public function test_visao_do_aluno_nao_expoe_o_gabarito(): void
    {
        $data = StudentExamResource::make($this->exam())->response()->getData(true)['data'];

        $this->assertSame([100, 101], array_column($data['questions'][0]['options'], 'id'));
        $this->assertStringNotContainsString('is_correct', json_encode($data));
    }

    public function test_visao_do_professor_inclui_o_gabarito(): void
    {
        $data = ExamResource::make($this->exam())->response()->getData(true)['data'];

        $this->assertSame([true, false], array_column($data['questions'][0]['options'], 'is_correct'));
    }
}
