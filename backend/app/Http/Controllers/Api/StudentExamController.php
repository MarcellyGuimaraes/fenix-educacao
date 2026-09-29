<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAttemptRequest;
use App\Http\Resources\AttemptResultResource;
use App\Http\Resources\Student\StudentExamListResource;
use App\Http\Resources\Student\StudentExamResource;
use App\Models\Exam;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class StudentExamController extends Controller
{
    public function __construct(
        private readonly ExamAttemptService $attempts,
    ) {}

    #[OA\Get(
        path: '/student/exams',
        tags: ['Aluno'],
        summary: 'Lista provas disponíveis com o status da tentativa do aluno',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentExamListItem')),
            ])),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Student $student */
        $student = $request->attributes->get('student');

        return StudentExamListResource::collection($this->attempts->availableFor($student));
    }

    #[OA\Get(
        path: '/student/exams/{exam}',
        tags: ['Aluno'],
        summary: 'Prova para responder (sem o gabarito)',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Prova não encontrada'),
        ]
    )]
    public function show(Exam $exam): StudentExamResource
    {
        return StudentExamResource::make($this->attempts->examForStudent($exam));
    }

    #[OA\Post(
        path: '/student/exams/{exam}/attempts',
        tags: ['Aluno'],
        summary: 'Submete respostas e recebe a correção automática',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AttemptSubmission')),
        responses: [
            new OA\Response(response: 201, description: 'Corrigida', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AttemptResult'),
            ])),
            new OA\Response(response: 409, description: 'Aluno já realizou esta prova'),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function submit(SubmitAttemptRequest $request, Exam $exam): JsonResponse
    {
        /** @var Student $student */
        $student = $request->attributes->get('student');

        $attempt = $this->attempts->submit($exam, $student, $request->validated()['answers']);

        return AttemptResultResource::make($attempt)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
