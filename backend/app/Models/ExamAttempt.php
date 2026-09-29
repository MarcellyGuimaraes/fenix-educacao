<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\ExamAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'score',
        'total_questions',
        'percentage',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total_questions' => 'integer',
            'percentage' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Prova realizada.
     *
     * @return BelongsTo<Exam, ExamAttempt>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Aluno que realizou a tentativa.
     *
     * @return BelongsTo<Student, ExamAttempt>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Respostas dadas nesta tentativa.
     *
     * @return HasMany<AttemptAnswer>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
