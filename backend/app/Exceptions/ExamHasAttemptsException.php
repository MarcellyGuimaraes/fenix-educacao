<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lançada quando o professor tenta editar ou excluir uma prova que já foi
 * respondida. As duas operações apagariam em cascata as respostas dos alunos,
 * e o histórico de tentativas precisa ser preservado.
 */
class ExamHasAttemptsException extends Exception
{
    public static function forUpdate(): self
    {
        return new self('Esta prova já foi respondida e não pode ser editada.');
    }

    public static function forDelete(): self
    {
        return new self('Esta prova já foi respondida e não pode ser excluída.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
