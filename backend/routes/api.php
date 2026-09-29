<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StudentExamController;
use Illuminate\Support\Facades\Route;

// Perfis disponíveis (telas de acesso — o desafio dispensa login).
Route::get('teachers', [ProfileController::class, 'teachers']);
Route::get('students', [ProfileController::class, 'students']);

// Área do professor.
Route::middleware('profile:teacher')->group(function (): void {
    Route::apiResource('exams', ExamController::class);

    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('dashboard/ranking', [DashboardController::class, 'ranking']);
});

// Área do aluno.
Route::middleware('profile:student')->prefix('student')->group(function (): void {
    Route::get('exams', [StudentExamController::class, 'index']);
    Route::get('exams/{exam}', [StudentExamController::class, 'show']);
    Route::post('exams/{exam}/attempts', [StudentExamController::class, 'submit']);

    Route::get('attempts/{attempt}', [AttemptController::class, 'show']);
});
