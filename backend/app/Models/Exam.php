<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    /** @use HasFactory<\Database\Factories\ExamFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'description',
    ];

    public const NOT_OWNER_MESSAGE = 'Esta prova pertence a outro professor.';

    /**
     * Indica se o professor é o autor da prova.
     */
    public function isOwnedBy(Teacher $teacher): bool
    {
        return (int) $this->teacher_id === (int) $teacher->id;
    }

    /**
     * Professor autor da prova.
     *
     * @return BelongsTo<Teacher, Exam>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Questões da prova.
     *
     * @return HasMany<Question>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Tentativas realizadas nesta prova.
     *
     * @return HasMany<ExamAttempt>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
