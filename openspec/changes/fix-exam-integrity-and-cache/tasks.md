# Tasks

## 1. Bloquear edição de prova já respondida (backend)

- [x] 1.1 Criar `app/Exceptions/ExamHasAttemptsException.php` no formato de `ExamAlreadyAttemptedException` (render → 409 com `message`); verificar que `php artisan test` continua verde
- [x] 1.2 Em `ExamService::update`, dentro da transação, recarregar a prova com `lockForUpdate()`, lançar `ExamHasAttemptsException` se `attempts()->exists()` e só então chamar o repositório (design D1/D2); verificar com o teste da 1.4
- [x] 1.3 Em `ExamService::find`, carregar `loadCount('attempts')` para que `GET /exams/{id}` exponha `attempts_count`; verificar com teste que `data.attempts_count` vem na resposta
- [x] 1.4 Adicionar testes em `tests/Feature/Teacher/ExamManagementTest.php`: (a) `PUT` em prova com tentativa → 409 e nada muda (título, questões e respostas da tentativa); (b) `PUT` inválido em prova com tentativa → 422; (c) o teste existente de atualização sem tentativas continua 200; verificar com `php artisan test --filter=ExamManagementTest`
- [x] 1.5 Documentar a resposta 409 no atributo `OA\Put` de `ExamController::update`; verificar que `php artisan l5-swagger:generate` roda sem erro e que o 409 aparece em `/docs`

## 2. Exclusão de prova invalida o dashboard

- [x] 2.1 Injetar `DashboardService` em `ExamService` e chamar `flushCache()` após `delete` (design D3); verificar com o teste da 2.2
- [x] 2.2 Adicionar teste em `tests/Feature/DashboardTest.php`: consultar summary/ranking (popula o cache), excluir a prova via `DELETE /exams/{id}` e verificar que `total_attempts` zera e o ranking fica vazio; verificar com `php artisan test --filter=DashboardTest`

## 3. Submissão concorrente retorna 409

- [x] 3.1 Confirmar que `Illuminate\Database\UniqueConstraintViolationException` existe no `vendor` (Laravel 12) e, em `ExamAttemptService::submit`, capturá-la em volta da transação de gravação e relançar `ExamAlreadyAttemptedException`
- [x] 3.2 Na transação de gravação de `submit`, travar a linha da prova com `lockForUpdate()` (design D2); verificar que os testes de submissão existentes continuam verdes
- [x] 3.3 Adicionar teste em `tests/Unit/ExamAttemptServiceTest.php` com o repositório de tentativas mockado (`existsForStudentAndExam` → false, `createWithAnswers` lança `UniqueConstraintViolationException`) esperando `ExamAlreadyAttemptedException`, e confirmar que o cache não é invalidado; verificar com `php artisan test --filter=ExamAttemptServiceTest`

## 4. Store de cache padrão

- [x] 4.1 Alterar o padrão em `config/cache.php` para `env('CACHE_STORE', 'redis')`; verificar que `php artisan test` continua verde (o phpunit força `array`) e que `docker compose exec app php artisan tinker --execute="echo config('cache.default');"` imprime `redis`

## 5. Frontend do professor

- [x] 5.1 Em `views/teacher/ExamListView.vue`, desabilitar "Editar" quando `exam.attempts_count > 0`, com `title` explicando o motivo; verificar no navegador com uma prova respondida
- [x] 5.2 Em `views/teacher/ExamFormView.vue` (modo edição), exibir um aviso e desabilitar "Salvar" quando `attempts_count > 0`; verificar acessando `/professor/provas/{id}/editar` diretamente pela URL
- [x] 5.3 Em `ExamFormView.vue`, tratar 409 no `catch` exibindo `e.response.data.message` sem navegar; verificar forçando o cenário (por exemplo, submeter como aluno em outra aba enquanto o formulário está aberto)

## 6. Documentação

- [x] 6.1 Atualizar `docs/ARCHITECTURE.md`: fluxo F1 (409 com tentativas), F3 (invalidação também na exclusão) e a seção 6 com os quatro pontos marcados como resolvidos; verificar relendo o documento contra o código final
- [x] 6.2 Atualizar o `README.md`: incluir o 409 da edição na lista de erros, registrar a invalidação na exclusão na seção de Cache e adicionar o link para `docs/ARCHITECTURE.md`; verificar que os links funcionam

## 7. Verificação do fluxo completo (integração)

- [x] 7.1 Rodar a suíte completa com cobertura (`docker compose exec app php artisan test --coverage`); verificar que tudo está verde e que a cobertura não caiu abaixo de ~94%
- [x] 7.2 Subir o ambiente (`docker compose up --build`) e, via `curl` em `http://localhost:8080/api` com os headers de perfil, executar em ordem: `GET /teachers` e `/students` → `POST /exams` (201) → `GET /student/exams/{id}` sem `is_correct` → `POST .../attempts` (201, score e %) → reenviar (409) → `GET /dashboard/summary` e `/ranking` incluindo a tentativa → `PUT /exams/{id}` (409) → `DELETE /exams/{id}` (204) → dashboard sem a tentativa; verificar cada status e corpo esperado
- [x] 7.3 Teste manual na SPA (`http://localhost:5173`): perfil Professor cria prova → perfil Aluno responde e vê o resultado → Aluno não consegue refazer → Professor vê "Editar" desabilitado e o dashboard atualizado → Professor exclui a prova e o dashboard se ajusta; verificar a ausência de erros no console do navegador
