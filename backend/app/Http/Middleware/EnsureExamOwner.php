<?php

namespace App\Http\Middleware;

use App\Models\Exam;
use App\Models\Teacher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o professor identificado (via `profile:teacher`) é o autor da
 * prova da rota. Roda como middleware — antes da validação do FormRequest —
 * para que o 403 venha antes de um 422 ou de um 409.
 */
class EnsureExamOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Teacher $teacher */
        $teacher = $request->attributes->get('teacher');
        $exam = $request->route('exam');

        if ($exam instanceof Exam && ! $exam->isOwnedBy($teacher)) {
            abort(403, Exam::NOT_OWNER_MESSAGE);
        }

        return $next($request);
    }
}
