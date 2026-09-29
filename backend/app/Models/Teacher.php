<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    /** @use HasFactory<\Database\Factories\TeacherFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Provas criadas pelo professor.
     *
     * @return HasMany<Exam>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
