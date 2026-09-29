<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Endpoints auxiliares para as telas de acesso (o desafio dispensa login).
 */
class ProfileController extends Controller
{
    #[OA\Get(
        path: '/teachers',
        tags: ['Perfis'],
        summary: 'Lista os professores disponíveis',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Profile')),
                ])
            ),
        ]
    )]
    public function teachers(): AnonymousResourceCollection
    {
        return ProfileResource::collection(Teacher::orderBy('name')->get());
    }

    #[OA\Get(
        path: '/students',
        tags: ['Perfis'],
        summary: 'Lista os alunos disponíveis',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Profile')),
                ])
            ),
        ]
    )]
    public function students(): AnonymousResourceCollection
    {
        return ProfileResource::collection(Student::orderBy('name')->get());
    }
}
