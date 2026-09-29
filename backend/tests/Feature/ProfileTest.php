<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_professores(): void
    {
        Teacher::factory()->count(2)->create();

        $this->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_lista_alunos(): void
    {
        Student::factory()->count(3)->create();

        $this->getJson('/api/students')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
