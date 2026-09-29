<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lançada quando um aluno tenta realizar uma prova que já respondeu.
 */
class ExamAlreadyAttemptedException extends Exception
{
    protected $message = 'Este aluno já realizou esta prova.';

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
