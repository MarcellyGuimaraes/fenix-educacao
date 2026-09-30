<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Models\Teacher;
use App\Support\Identifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o perfil que está agindo (professor ou aluno) a partir dos headers
 * X-User-Role e X-User-Id. O desafio dispensa autenticação real: apenas dois
 * acessos (professor e aluno), então confiamos nesses headers só para saber
 * qual perfil está operando e disponibilizamos a entidade resolvida na request.
 */
class EnsureProfile
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $requestRole = $request->header('X-User-Role');

        if ($requestRole !== $role) {
            abort(403, 'Perfil não autorizado para esta ação.');
        }

        // Id malformado é tratado como perfil inexistente, sem ir ao banco.
        $id = $request->header('X-User-Id');
        $id = Identifier::isValid($id) ? (int) $id : null;

        if ($role === 'teacher') {
            $teacher = $id ? Teacher::find($id) : null;
            if (! $teacher) {
                abort(401, 'Professor não identificado.');
            }
            $request->attributes->set('teacher', $teacher);
        }

        if ($role === 'student') {
            $student = $id ? Student::find($id) : null;
            if (! $student) {
                abort(401, 'Aluno não identificado.');
            }
            $request->attributes->set('student', $student);
        }

        return $next($request);
    }
}
