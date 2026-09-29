<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttemptResultResource;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function __construct(
        private readonly ExamAttemptService $attempts,
    ) {}

    public function show(Request $request, ExamAttempt $attempt): AttemptResultResource
    {
        /** @var Student $student */
        $student = $request->attributes->get('student');

        // Um aluno só pode ver o resultado das próprias tentativas.
        abort_if($attempt->student_id !== $student->id, 403, 'Acesso negado a esta tentativa.');

        return AttemptResultResource::make($this->attempts->result($attempt));
    }
}
