# Tasks

## 1. Ids não numéricos não geram 500 (profile-access)

- [x] 1.1 Criar a constante única do formato de id (por exemplo `App\Support\Identifier::PATTERN = '[1-9][0-9]{0,17}'`) e registrar `Route::pattern('exam', …)` e `Route::pattern('attempt', …)`; verificar com `php artisan route:list` que as rotas `{exam}`/`{attempt}` exibem a restrição
- [x] 1.2 Em `EnsureProfile`, validar `X-User-Id` contra a mesma expressão antes de `find()`, abortando com o 401 atual ("Professor não identificado." / "Aluno não identificado."); verificar pelo teste da 1.3
- [x] 1.3 Criar `tests/Feature/InvalidIdentifierTest.php` cobrindo: header `abc`, `1 OR 1=1`, vazio e `99999999999999999999` → 401 (professor e aluno); `GET/PUT/DELETE /api/exams/abc`, `GET /api/student/exams/abc`, `POST /api/student/exams/abc/attempts`, `GET /api/student/attempts/abc` e ids com 20 dígitos → 404 em JSON; verificar com `php artisan test --filter=InvalidIdentifierTest`
- [x] 1.4 Verificar manualmente no PostgreSQL (`docker compose up --build`) que `curl -H "X-User-Role: teacher" -H "X-User-Id: abc" localhost:8080/api/exams` responde 401 e que `/api/exams/abc` com headers válidos responde 404, e não 500

## 2. Dono da prova e listagem filtrada (exam-management)

- [x] 2.1 Adicionar `Exam::isOwnedBy(Teacher $teacher): bool` e o middleware `EnsureExamOwner` (alias `exam.owner`, registrado em `bootstrap/app.php`) que aborta com 403 "Esta prova pertence a outro professor."; aplicá-lo a `show`, `update` e `destroy` de `exams` em `routes/api.php`; verificar com os testes da 2.3
- [x] 2.2 Trocar `allWithCounts()` por `forTeacherWithCounts(int $teacherId)` no contrato e no `ExamRepository` (`where('teacher_id', …)`), e fazer `ExamService::list(Teacher)`/`ExamController::index(Request)` passarem o professor do request; verificar com os testes da 2.3
- [x] 2.3 Em `ExamManagementTest`, adicionar os casos: listagem só com provas do professor A; professor sem provas → `data` vazio; `GET`/`PUT`/`DELETE` de prova de outro professor → 403 sem alteração (prova e tentativas continuam no banco); `PUT` inválido em prova de outro professor → 403 (não 422); `PUT` em prova respondida de outro professor → 403 (não 409); manter os testes existentes verdes; verificar com `php artisan test --filter=ExamManagementTest`
- [x] 2.4 Atualizar as anotações OpenAPI de `ExamController` (resposta 403 em show/update/destroy; descrição da listagem "provas do professor") e verificar com `php artisan l5-swagger:generate` sem erros
- [x] 2.5 Em `ExamFormView.vue` e `ExamListView.vue`, exibir a `message` da API ao receber 403 (carregar/salvar/excluir) em vez da mensagem genérica; verificar com `npm run build` sem erros e, no navegador, abrindo `/teacher/exams/{id}/edit` de uma prova de outro professor
- [x] 2.6 Acrescentar ao `DatabaseSeeder` um segundo professor com uma prova própria e verificar com `php artisan migrate:fresh --seed` que cada professor lista só as próprias provas

- [x] 2.7 Na `HomeView.vue`, trocar o acesso fixo ao primeiro professor por um seletor de professor (como o de aluno), para ser possível entrar como o segundo professor do seed; verificar com `npm run build` sem erros e, no navegador, entrando como "Prof. Bruno Costa"

## 3. Dashboard com escopo do professor e novas métricas (teacher-dashboard)

- [x] 3.1 Refatorar `DashboardRepositoryInterface`/`DashboardRepository` para receberem `int $teacherId` em todos os métodos (query base com `whereIn('exam_id', Exam::select('id')->where('teacher_id', …))`) e `?int $examId` em `ranking`; verificar com os testes da 3.5
- [x] 3.2 Adicionar ao repositório `examMetrics(int $teacherId)` (`withCount`/`withAvg`/`withMax`/`withMin` sobre `attempts.percentage`, ordenado por título) e `studentAverages(int $teacherId, int $perPage, int $page)` (agrupado por aluno, `COUNT`/`AVG`, join em `students`, ordenado por média desc e nome, paginado); verificar com os testes da 3.5
- [x] 3.3 Atualizar `DashboardService`: todos os métodos recebem o `Teacher`; chaves de cache `dashboard:{teacher}:…` incluindo o filtro do ranking; novos `examMetrics()` e `studentAverages()` devolvendo DTOs com `round(…, 2)`/cast para `float` e `difference_from_average`; `flushCache()` continua global; verificar com os testes da 3.5
- [x] 3.4 Atualizar `DashboardController`: passar o professor do request; novas ações `exams()` e `students()` (paginação `page`/`per_page` 1..50) com rotas `GET dashboard/exams` e `GET dashboard/students`; `ranking` validando `exam_id` (`nullable|integer|min:1` → 422), 404 para prova inexistente e 403 via `Exam::isOwnedBy`; verificar com os testes da 3.5
- [x] 3.5 Em `DashboardTest`, adicionar os casos: resumo e ranking ignoram tentativas de provas de outro professor; cache não vaza entre professores (A consulta, B consulta, B vê só as dele); métricas por prova (média/melhor/pior/total e prova sem tentativas com `null`); aluno × média (ordem, `attempts_count`, `difference_from_average` negativa e aluno sem tentativas ausente, paginação); ranking com `exam_id` (posições a partir de 1, `meta.total` do filtro), `exam_id=abc` → 422, prova inexistente → 404, prova de outro professor → 403; ajustar os testes existentes de dashboard e de invalidação de cache (`StudentExamTest`, `ExamManagementTest`) para o escopo do professor; verificar com `php artisan test`
- [x] 3.6 Documentar no `ApiDoc.php` os schemas `ExamMetric` e `StudentAverage`, anotar os novos endpoints e o parâmetro `exam_id` do ranking, e verificar com `php artisan l5-swagger:generate` sem erros

## 4. Dashboard na interface (teacher-dashboard)

- [x] 4.1 Em `DashboardView.vue`, carregar `dashboard/exams` e exibir a tabela "Média por prova" (título, tentativas, média, melhor, pior, com "—" quando `null`); verificar no navegador com o seed
- [x] 4.2 Adicionar a tabela "Aluno × média" com paginação própria e `difference_from_average` com sinal e cor (positiva/negativa); verificar no navegador
- [x] 4.3 Adicionar ao ranking o seletor de prova (opção "Todas as provas" + provas de `dashboard/exams`) que recarrega a página 1 com `exam_id`, com a paginação preservando o filtro; mensagens de lista vazia nas novas tabelas; verificar no navegador e com `npm run build` sem erros

## 5. Documentação e verificação final

- [x] 5.1 Atualizar o `README.md` (tabela de endpoints: `dashboard/exams`, `dashboard/students`, `exam_id` no ranking; seção de regras: provas e dashboard restritos ao professor, 403 para prova de outro professor, 401/404 para ids inválidos) e o `docs/ARCHITECTURE.md` (fluxo F3 e middlewares); verificar que os endpoints citados batem com `php artisan route:list`
- [x] 5.2 Rodar a suíte completa (`docker compose exec app php artisan test`) e o `openspec validate apply-code-review-improvements --strict`, garantindo que tudo passe
