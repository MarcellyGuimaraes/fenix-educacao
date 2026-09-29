<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttemptResultResource;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AttemptController extends Controller
{
    public function __construct(
        private readonly ExamAttemptService $attempts,
    ) {}

    #[OA\Get(
        path: '/student/attempts/{attempt}',
        tags: ['Aluno'],
        summary: 'Resultado detalhado de uma tentativa do próprio aluno',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'attempt', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AttemptResult'),
            ])),
            new OA\Response(response: 403, description: 'Tentativa de outro aluno'),
            new OA\Response(response: 404, description: 'Tentativa não encontrada'),
        ]
    )]
    public function show(Request $request, ExamAttempt $attempt): AttemptResultResource
    {
        /** @var Student $student */
        $student = $request->attributes->get('student');

        // Um aluno só pode ver o resultado das próprias tentativas.
        abort_if($attempt->student_id !== $student->id, 403, 'Acesso negado a esta tentativa.');

        return AttemptResultResource::make($this->attempts->result($attempt));
    }
}
