<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lançada quando o professor tenta editar uma prova que já foi respondida.
 * Editar recriaria as questões e apagaria em cascata as respostas dos alunos.
 */
class ExamHasAttemptsException extends Exception
{
    protected $message = 'Esta prova já foi respondida e não pode ser editada.';

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
