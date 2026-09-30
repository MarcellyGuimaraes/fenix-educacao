# Tasks

## 1. Métricas do dashboard (teacher-dashboard)

- [ ] 1.1 `DashboardRepository`: subquery de médias por prova, `examsAveragePercentage` e `studentAverages` com `difference_from_exam_average` e a nova ordenação
- [ ] 1.2 `DashboardService`: `exams_average_percentage` no resumo e `difference_from_exam_average` nos alunos
- [ ] 1.3 `DashboardView`: cartões "Média das provas" e "Média por tentativa" e coluna de desvio por prova
- [ ] 1.4 `DashboardTest` com os novos cenários; OpenAPI atualizado

## 2. Exclusão preserva o histórico (exam-management)

- [ ] 2.1 `ExamHasAttemptsException::forUpdate()`/`forDelete()`; `ExamService::delete()` trava a prova e bloqueia com 409
- [ ] 2.2 Migration: FK `exam_attempts.exam_id` → `restrictOnDelete`
- [ ] 2.3 `ExamListView`: "Excluir" desabilitado para prova respondida e mensagem do 409
- [ ] 2.4 Testes: exclusão de prova respondida → 409 com tentativas preservadas; exclusão sem tentativas → 204 e some das métricas; OpenAPI com o 409

## 3. Submissão serializada (exam-attempts)

- [ ] 3.1 `ExamAttemptService::submit()` com lock, verificação, leitura e correção dentro da transação
- [ ] 3.2 Testes existentes de submissão continuam verdes

## 4. Seed

- [ ] 4.1 `DatabaseSeeder` cria tentativas de exemplo via `ExamAttemptService`, de forma idempotente

## 5. Verificação final

- [ ] 5.1 Suíte completa verde; `npm run build` sem erros; `openspec validate apply-second-code-review --strict`
- [ ] 5.2 `migrate:fresh --seed` no PostgreSQL e conferência do dashboard no navegador
- [ ] 5.3 README atualizado
