# Design

## Context

Motivação em `proposal.md`; requisitos em `specs/`. Estado atual relevante:

- `DashboardService::studentAverages()` calcula `difference_from_average` como média do aluno − `AVG(percentage)` de todas as tentativas do professor.
- `ExamService::delete()` remove a prova; `exam_attempts.exam_id` é `cascadeOnDelete`, então tentativas e respostas somem junto.
- `ExamAttemptService::submit()` faz `existsForStudentAndExam`, `$exam->load('questions.options')` e a correção fora da transação; só a gravação fica dentro de `DB::transaction` com `lockForUpdate`.

## Goals / Non-Goals

**Goals:**
- Métricas do dashboard com semântica explícita e comparação justa entre alunos.
- Histórico de tentativas preservado na API e no banco.
- Nenhum 500 causado por edição concorrente durante a submissão.

**Non-Goals:**
- Refatorações de camadas, novos testes unitários e ajustes de desempenho.
- Associar provas a alunos ou turmas.

## Decisions

### 1. Desvio do aluno calculado prova a prova, no banco
`studentAverages` junta as tentativas a uma subquery com a média de cada prova do professor (`SELECT exam_id, AVG(percentage) ... GROUP BY exam_id`) e agrega por aluno `AVG(exam_attempts.percentage - exam_averages.exam_average)`. Continua sendo uma única query paginada.
- *Alternativa descartada:* z-score. Mais preciso estatisticamente, mas difícil de explicar na interface e instável com poucas tentativas.
- *Alternativa descartada:* manter o nome `difference_from_average`. A semântica mudou; um nome novo evita interpretação errada.

### 2. Duas médias no resumo, com nomes explícitos
`average_percentage` continua sendo a média por tentativa. `exams_average_percentage` é o `AVG` sobre a mesma subquery de médias por prova. A interface mostra "Média das provas" (termo do enunciado) e "Média por tentativa".

### 3. Exclusão bloqueada em vez de soft delete
`ExamService::delete()` trava a prova e lança `ExamHasAttemptsException::forDelete()` (409) se houver tentativas, espelhando a regra de edição. Uma migration troca a FK `exam_attempts.exam_id` para `restrictOnDelete`, garantindo a regra também no banco.
- *Alternativa descartada:* `SoftDeletes`. Obrigaria `withTrashed()` no resultado do aluno, no dashboard e no binding de rotas, e deixaria em aberto se a prova "excluída" entra nas métricas.

### 4. Correção dentro da transação
`submit()` passa a ser: `DB::transaction` → `lockForUpdate` da prova → verificação de tentativa existente → leitura das questões → correção → gravação. O `catch (UniqueConstraintViolationException)` continua como garantia final. Como a edição também trava a linha da prova, uma das duas operações sempre enxerga o resultado da outra.

### 5. Seed com tentativas
O `DatabaseSeeder` submete respostas pelo próprio `ExamAttemptService` (mesma correção da API), pulando alunos que já têm tentativa, para continuar idempotente a cada subida do container.

## Risks / Trade-offs

- [Mudança de contrato em `dashboard/students` e `DELETE /exams`] → frontend, Swagger e README atualizados na mesma change.
- [Migration altera FK em tabela existente] → no SQLite o Laravel recria a tabela; no PostgreSQL é `ALTER TABLE`.
