<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\AttemptAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'option_id',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * Tentativa a que a resposta pertence.
     *
     * @return BelongsTo<ExamAttempt, AttemptAnswer>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /**
     * Questão respondida.
     *
     * @return BelongsTo<Question, AttemptAnswer>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Alternativa escolhida pelo aluno.
     *
     * @return BelongsTo<Option, AttemptAnswer>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
