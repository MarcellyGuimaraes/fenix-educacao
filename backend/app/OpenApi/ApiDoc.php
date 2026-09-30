<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Definições globais da documentação OpenAPI (Info, servidor, tags e headers).
 * Os schemas ficam em classes-âncora próprias mais abaixo neste arquivo — cada
 * `#[OA\Schema]` precisa estar em sua própria classe para ser registrado.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Fênix Provas API',
    description: <<<'DESC'
        API de provas online (Desafio Fênix).

        **Autenticação:** o desafio dispensa login. Cada requisição às áreas de
        professor/aluno deve enviar os headers `X-User-Role` (`teacher`|`student`)
        e `X-User-Id` (id do perfil). Use `GET /teachers` e `GET /students` para
        obter os ids disponíveis.
        DESC,
)]
#[OA\Server(url: 'http://localhost:8080/api', description: 'Ambiente local (Docker)')]
#[OA\Tag(name: 'Perfis', description: 'Perfis disponíveis (sem login)')]
#[OA\Tag(name: 'Provas (Professor)', description: 'Gestão de provas')]
#[OA\Tag(name: 'Dashboard (Professor)', description: 'Métricas e ranking')]
#[OA\Tag(name: 'Aluno', description: 'Responder provas e ver resultados')]
#[OA\Parameter(
    parameter: 'RoleHeader',
    name: 'X-User-Role',
    in: 'header',
    required: true,
    description: 'Perfil que está agindo',
    schema: new OA\Schema(type: 'string', enum: ['teacher', 'student'])
)]
#[OA\Parameter(
    parameter: 'UserIdHeader',
    name: 'X-User-Id',
    in: 'header',
    required: true,
    description: 'Id do perfil (professor ou aluno)',
    schema: new OA\Schema(type: 'integer', example: 1)
)]
class ApiDoc
{
}

#[OA\Schema(
    schema: 'OptionInput',
    required: ['text', 'is_correct'],
    properties: [
        new OA\Property(property: 'text', type: 'string', example: 'Brasília'),
        new OA\Property(property: 'is_correct', type: 'boolean', example: true),
    ]
)]
class OptionInputSchema
{
}

#[OA\Schema(
    schema: 'QuestionInput',
    required: ['statement', 'options'],
    properties: [
        new OA\Property(property: 'statement', type: 'string', example: 'Qual é a capital do Brasil?'),
        new OA\Property(property: 'options', type: 'array', minItems: 2, items: new OA\Items(ref: '#/components/schemas/OptionInput')),
    ]
)]
class QuestionInputSchema
{
}

#[OA\Schema(
    schema: 'ExamInput',
    required: ['title', 'questions'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Conhecimentos Gerais'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Avaliação introdutória.'),
        new OA\Property(property: 'questions', type: 'array', minItems: 1, items: new OA\Items(ref: '#/components/schemas/QuestionInput')),
    ]
)]
class ExamInputSchema
{
}

#[OA\Schema(
    schema: 'Option',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'text', type: 'string', example: 'Brasília'),
        new OA\Property(property: 'is_correct', type: 'boolean', example: true),
        new OA\Property(property: 'order', type: 'integer', example: 2),
    ]
)]
class OptionSchema
{
}

#[OA\Schema(
    schema: 'Question',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'statement', type: 'string', example: 'Qual é a capital do Brasil?'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(ref: '#/components/schemas/Option')),
    ]
)]
class QuestionSchema
{
}

#[OA\Schema(
    schema: 'Exam',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Conhecimentos Gerais'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'questions_count', type: 'integer', example: 3),
        new OA\Property(property: 'attempts_count', type: 'integer', example: 5),
        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(ref: '#/components/schemas/Question')),
    ]
)]
class ExamSchema
{
}

#[OA\Schema(
    schema: 'Profile',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Prof. Ana Souza'),
    ]
)]
class ProfileSchema
{
}

#[OA\Schema(
    schema: 'StudentExamListItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'questions_count', type: 'integer', example: 3),
        new OA\Property(property: 'attempted', type: 'boolean', example: false),
        new OA\Property(property: 'attempt_id', type: 'integer', nullable: true),
        new OA\Property(property: 'percentage', type: 'number', format: 'float', nullable: true),
    ]
)]
class StudentExamListItemSchema
{
}

#[OA\Schema(
    schema: 'AttemptSubmission',
    required: ['answers'],
    properties: [
        new OA\Property(
            property: 'answers',
            type: 'array',
            items: new OA\Items(
                required: ['question_id', 'option_id'],
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer', example: 1),
                    new OA\Property(property: 'option_id', type: 'integer', example: 2),
                ]
            )
        ),
    ]
)]
class AttemptSubmissionSchema
{
}

#[OA\Schema(
    schema: 'AttemptResult',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'exam_id', type: 'integer', example: 1),
        new OA\Property(property: 'exam_title', type: 'string'),
        new OA\Property(property: 'student_name', type: 'string'),
        new OA\Property(property: 'score', type: 'integer', example: 2),
        new OA\Property(property: 'total_questions', type: 'integer', example: 3),
        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 66.67),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'answers',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer'),
                    new OA\Property(property: 'statement', type: 'string'),
                    new OA\Property(property: 'chosen_option_id', type: 'integer'),
                    new OA\Property(property: 'chosen_option_text', type: 'string'),
                    new OA\Property(property: 'is_correct', type: 'boolean'),
                    new OA\Property(property: 'correct_option_id', type: 'integer'),
                    new OA\Property(property: 'correct_option_text', type: 'string'),
                ]
            )
        ),
    ]
)]
class AttemptResultSchema
{
}

#[OA\Schema(
    schema: 'DashboardSummary',
    properties: [
        new OA\Property(property: 'exams_average_percentage', type: 'number', format: 'float', description: 'Média das provas: média das médias de cada prova com tentativas (cada prova com o mesmo peso)', example: 65),
        new OA\Property(property: 'average_percentage', type: 'number', format: 'float', description: 'Média por tentativa: cada tentativa com o mesmo peso', example: 55.56),
        new OA\Property(property: 'total_attempts', type: 'integer', example: 3),
        new OA\Property(property: 'best', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'student_name', type: 'string'),
            new OA\Property(property: 'exam_title', type: 'string'),
            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
        ]),
        new OA\Property(property: 'worst', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'student_name', type: 'string'),
            new OA\Property(property: 'exam_title', type: 'string'),
            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
        ]),
    ]
)]
class DashboardSummarySchema
{
}

#[OA\Schema(
    schema: 'RankingItem',
    properties: [
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'attempt_id', type: 'integer', example: 2),
        new OA\Property(property: 'student_name', type: 'string', example: 'Maria Oliveira'),
        new OA\Property(property: 'exam_title', type: 'string'),
        new OA\Property(property: 'score', type: 'integer', example: 3),
        new OA\Property(property: 'total_questions', type: 'integer', example: 3),
        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 100),
    ]
)]
class RankingItemSchema
{
}

#[OA\Schema(
    schema: 'ExamMetric',
    properties: [
        new OA\Property(property: 'exam_id', type: 'integer', example: 1),
        new OA\Property(property: 'exam_title', type: 'string', example: 'Prova de Conhecimentos Gerais'),
        new OA\Property(property: 'attempts_count', type: 'integer', example: 3),
        new OA\Property(property: 'average_percentage', type: 'number', format: 'float', nullable: true, example: 66.67),
        new OA\Property(property: 'best_percentage', type: 'number', format: 'float', nullable: true, example: 100),
        new OA\Property(property: 'worst_percentage', type: 'number', format: 'float', nullable: true, example: 33.33),
    ]
)]
class ExamMetricSchema
{
}

#[OA\Schema(
    schema: 'StudentAverage',
    properties: [
        new OA\Property(property: 'student_id', type: 'integer', example: 2),
        new OA\Property(property: 'student_name', type: 'string', example: 'Maria Oliveira'),
        new OA\Property(property: 'attempts_count', type: 'integer', example: 2),
        new OA\Property(property: 'average_percentage', type: 'number', format: 'float', example: 90),
        new OA\Property(property: 'difference_from_exam_average', type: 'number', format: 'float', description: 'Média, nas tentativas do aluno, de "percentual do aluno − média daquela prova", em pontos percentuais (comparação prova a prova)', example: 17.5),
    ]
)]
class StudentAverageSchema
{
}

#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
    ]
)]
class PaginationMetaSchema
{
}

#[OA\Schema(
    schema: 'Message',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Esta prova pertence a outro professor.'),
    ]
)]
class MessageSchema
{
}

#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Cada questão deve ter exatamente uma alternativa correta.'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
class ValidationErrorSchema
{
}
