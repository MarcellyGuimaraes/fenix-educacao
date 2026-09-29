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

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ExamResource::collection($this->exams->list());
    }

    public function store(ExamRequest $request): JsonResponse
    {
        /** @var Teacher $teacher */
        $teacher = $request->attributes->get('teacher');

        $exam = $this->exams->create($teacher, $request->validated());

        return ExamResource::make($exam)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Exam $exam): ExamResource
    {
        return ExamResource::make($this->exams->find($exam));
    }

    public function update(ExamRequest $request, Exam $exam): ExamResource
    {
        return ExamResource::make($this->exams->update($exam, $request->validated()));
    }

    public function destroy(Exam $exam): Response
    {
        $this->exams->delete($exam);

        return response()->noContent();
    }
}
