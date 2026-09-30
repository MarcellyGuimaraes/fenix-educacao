<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\Teacher;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
    ) {}

    #[OA\Get(
        path: '/exams',
        tags: ['Provas (Professor)'],
        summary: 'Lista as provas com contadores',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Exam')),
                ])
            ),
            new OA\Response(response: 403, description: 'Perfil não autorizado'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return ExamResource::collection($this->exams->list());
    }

    #[OA\Post(
        path: '/exams',
        tags: ['Provas (Professor)'],
        summary: 'Cria uma prova com questões e alternativas',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ExamInput')),
        responses: [
            new OA\Response(response: 201, description: 'Criada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Exam'),
            ])),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(ExamRequest $request): JsonResponse
    {
        /** @var Teacher $teacher */
        $teacher = $request->attributes->get('teacher');

        $exam = $this->exams->create($teacher, $request->validated());

        return ExamResource::make($exam)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/exams/{exam}',
        tags: ['Provas (Professor)'],
        summary: 'Detalha uma prova (com gabarito)',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Exam'),
            ])),
            new OA\Response(response: 404, description: 'Prova não encontrada'),
        ]
    )]
    public function show(Exam $exam): ExamResource
    {
        return ExamResource::make($this->exams->find($exam));
    }

    #[OA\Put(
        path: '/exams/{exam}',
        tags: ['Provas (Professor)'],
        summary: 'Atualiza uma prova (substitui as questões); bloqueado se já houver tentativas',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ExamInput')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Exam'),
            ])),
            new OA\Response(response: 409, description: 'Prova já respondida não pode ser editada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Esta prova já foi respondida e não pode ser editada.'),
            ])),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(ExamRequest $request, Exam $exam): ExamResource
    {
        return ExamResource::make($this->exams->update($exam, $request->validated()));
    }

    #[OA\Delete(
        path: '/exams/{exam}',
        tags: ['Provas (Professor)'],
        summary: 'Exclui uma prova',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/RoleHeader'),
            new OA\Parameter(ref: '#/components/parameters/UserIdHeader'),
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Excluída'),
            new OA\Response(response: 404, description: 'Prova não encontrada'),
        ]
    )]
    public function destroy(Exam $exam): Response
    {
        $this->exams->delete($exam);

        return response()->noContent();
    }
}
