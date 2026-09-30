# Tasks

## 1. Métricas do dashboard (teacher-dashboard)

- [x] 1.1 `DashboardRepository`: subquery de médias por prova, `examsAveragePercentage` e `studentAverages` com `difference_from_exam_average` e a nova ordenação
- [x] 1.2 `DashboardService`: `exams_average_percentage` no resumo e `difference_from_exam_average` nos alunos
- [x] 1.3 `DashboardView`: cartões "Média das provas" e "Média por tentativa" e coluna de desvio por prova
- [x] 1.4 `DashboardTest` com os novos cenários; OpenAPI atualizado

## 2. Exclusão preserva o histórico (exam-management)

- [x] 2.1 `ExamHasAttemptsException::forUpdate()`/`forDelete()`; `ExamService::delete()` trava a prova e bloqueia com 409
- [x] 2.2 Migration: FK `exam_attempts.exam_id` → `restrictOnDelete`
- [x] 2.3 `ExamListView`: "Excluir" desabilitado para prova respondida e mensagem do 409
- [x] 2.4 Testes: exclusão de prova respondida → 409 com tentativas preservadas; exclusão sem tentativas → 204 e some das métricas; OpenAPI com o 409

## 3. Submissão serializada (exam-attempts)

- [x] 3.1 `ExamAttemptService::submit()` com lock, verificação, leitura e correção dentro da transação
- [x] 3.2 Testes existentes de submissão continuam verdes

## 4. Seed

- [x] 4.1 `DatabaseSeeder` cria tentativas de exemplo via `ExamAttemptService`, de forma idempotente

## 5. Verificação final

- [x] 5.1 Suíte completa verde; `npm run build` sem erros; `openspec validate apply-second-code-review --strict`
- [x] 5.2 Migration e seed no PostgreSQL (banco local pelo entrypoint e banco limpo pela suíte rodando em `pgsql`) e conferência do dashboard no navegador
- [x] 5.3 README atualizado
