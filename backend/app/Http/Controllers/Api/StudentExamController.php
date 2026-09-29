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

class StudentExamController extends Controller
{
    public function __construct(
        private readonly ExamAttemptService $attempts,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Student $student */
        $student = $request->attributes->get('student');

        return StudentExamListResource::collection($this->attempts->availableFor($student));
    }

    public function show(Exam $exam): StudentExamResource
    {
        return StudentExamResource::make($this->attempts->examForStudent($exam));
    }

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
