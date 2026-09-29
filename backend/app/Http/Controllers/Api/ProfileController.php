<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints auxiliares para as telas de acesso (o desafio dispensa login).
 */
class ProfileController extends Controller
{
    public function teachers(): AnonymousResourceCollection
    {
        return ProfileResource::collection(Teacher::orderBy('name')->get());
    }

    public function students(): AnonymousResourceCollection
    {
        return ProfileResource::collection(Student::orderBy('name')->get());
    }
}
