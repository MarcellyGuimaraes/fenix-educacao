<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureExamOwner;
use App\Models\Exam;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Middleware de autoria da prova testado isoladamente: a request e a rota são
 * montadas à mão, com modelos em memória (sem banco).
 */
class EnsureExamOwnerTest extends TestCase
{
    private function teacher(int $id): Teacher
    {
        $teacher = new Teacher(['name' => "Professor {$id}"]);
        $teacher->id = $id;

        return $teacher;
    }

    private function request(Teacher $teacher, ?Exam $exam): Request
    {
        $request = Request::create('/api/exams/1', 'PUT');
        $request->attributes->set('teacher', $teacher);

        $route = (new Route('PUT', 'api/exams/{exam}', fn () => null))->bind($request);
        $route->setParameter('exam', $exam);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function handle(Request $request): Response
    {
        return (new EnsureExamOwner)->handle($request, fn () => response('ok'));
    }

    public function test_autor_da_prova_segue_para_o_controller(): void
    {
        $exam = new Exam(['teacher_id' => 1, 'title' => 'Prova']);

        $response = $this->handle($this->request($this->teacher(1), $exam));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_outro_professor_recebe_403(): void
    {
        $exam = new Exam(['teacher_id' => 1, 'title' => 'Prova']);

        try {
            $this->handle($this->request($this->teacher(2), $exam));
            $this->fail('Era esperado um 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(Exam::NOT_OWNER_MESSAGE, $e->getMessage());
        }
    }

    public function test_autoria_compara_ids_mesmo_com_tipos_diferentes(): void
    {
        // Ids podem chegar como string do banco; a comparação é numérica.
        $exam = new Exam(['teacher_id' => '1', 'title' => 'Prova']);

        $this->assertTrue($exam->isOwnedBy($this->teacher(1)));
        $this->assertFalse($exam->isOwnedBy($this->teacher(2)));
    }
}
