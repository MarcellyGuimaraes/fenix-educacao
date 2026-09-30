# Mapa do Sistema — Fênix Provas Online

Documento de arquitetura: componentes, modelo de dados, fluxos críticos e
rastreabilidade dos requisitos do desafio técnico.

## 1. Visão geral (topologia Docker Compose)

```
                         +------------------------------+
   Navegador  ---------> |  frontend  (nginx 1.27)      |  :5173 -> :80
        |                |  dist/ do `vite build`       |
        |                |  SPA: Router/Pinia/Axios     |
        |                |  fallback -> index.html      |
        |                +------------------------------+
        |
        | HTTP/JSON (o JS da SPA chama a API direto)
        | headers X-User-Role / X-User-Id
        v
                         +------------------------------+
                         |  nginx 1.27 (imagem própria) |  :8080 -> :80
                         |  backend/public/ copiado     |
                         +-------------+----------------+
                                       | FastCGI :9000
                                       v
                         +------------------------------+
                         |  app  (PHP 8.4 / Laravel)    |
                         |  php-fpm + entrypoint        |
                         |  código + vendor/ na imagem  |
                         |  OPcache sem revalidação     |
                         +------+-------------+---------+
                                |             |
                     SQL (5432) |             | cache tags (6379)
                                v             v
                    +----------------+   +----------------+
                    | postgres 16    |   | redis 7        |
                    | fonte da       |   | cache do       |
                    | verdade        |   | dashboard      |
                    +----------------+   +----------------+
                     vol: postgres_data   vol: redis_data

   Rede: bridge "fenix". Modo padrão sem bind mounts: o código vai nas imagens
   (composer install / vite build no build).
   Boot do app: .env/APP_KEY -> espera Postgres -> migrate --seed
   -> l5-swagger:generate -> optimize (config/rotas/eventos/views) -> php-fpm
```

**Modo dev** (`docker-compose.dev.yml`, opcional): `app` monta `./backend` com o
`vendor/` no volume `backend_vendor`, OPcache revalidando e `APP_OPTIMIZE=false`
(o boot roda `optimize:clear` no lugar de `optimize`); `nginx` monta
`./backend/public`; `frontend` usa o estágio `dev` (Vite dev server com HMR,
porta 5173) com `./frontend` montado.

## 2. Componentes principais

### Backend: camadas

```
 Rota (routes/api.php)
   |
   v
 Middleware EnsureProfile ("profile:teacher" | "profile:student")
   |  resolve Teacher/Student -> $request->attributes
   v
 Controller (fino)  ---->  FormRequest (validação de entrada)
   |
   v
 Service (regra de negócio, transações, invalidação de cache)
   |
   v
 Repository (Interface -> implementação Eloquent, via RepositoryServiceProvider)
   |
   v
 Model (Eloquent)  -->  PostgreSQL
   ^
   |
 API Resource (serialização de saída; versão "Student" sem gabarito)
```

| Camada | Componentes | Responsabilidade |
|---|---|---|
| Middleware | `EnsureProfile`, `EnsureExamOwner` (`exam.owner`) | Identidade (perfil vem por header; `X-User-Id` malformado → 401) e dono da prova (403 em show/update/destroy, antes da validação) |
| Rotas | `Route::pattern` (`App\Support\Identifier`) | `{exam}`/`{attempt}` só casam com inteiros positivos de até 18 dígitos → 404, nunca 500 |
| Controllers | `ExamController`, `DashboardController`, `StudentExamController`, `AttemptController`, `ProfileController` | Orquestração HTTP |
| Validação | `ExamRequest`, `SubmitAttemptRequest` | Formato e regra de "exatamente 1 correta" |
| Services | `ExamService`, `ExamAttemptService`, `DashboardService` | CRUD de prova, correção automática, métricas e cache |
| Repositories | `ExamRepository`, `ExamAttemptRepository`, `DashboardRepository` | Consultas e escrita |
| Resources | `ExamResource` (com gabarito), `Student*Resource` (sem gabarito), `AttemptResultResource` | Contrato de saída |
| Erros | `ExamAlreadyAttemptedException` (409) + `shouldRenderJsonWhen(api/*)` | Todo erro da API sai em JSON |
| Docs | `App\OpenApi\ApiDoc` + l5-swagger | `/api/documentation` |

### Frontend

```
 main.js
   +-- router/index.js   guarda por meta.role (teacher|student)
   +-- stores/session.js Pinia + localStorage("fenix_session")
   +-- services/api.js   Axios + interceptor que injeta X-User-Role/X-User-Id
   +-- views/
        HomeView ................ escolhe o perfil (GET /teachers, /students)
        teacher/ExamListView .... GET /exams, DELETE /exams/{id}
        teacher/ExamFormView .... GET/POST/PUT /exams
        teacher/DashboardView ... GET /dashboard/summary, /exams, /students, /ranking?exam_id
        student/ExamListView .... GET /student/exams
        student/ExamTakeView .... GET /student/exams/{id}, POST .../attempts
        student/ResultView ...... GET /student/attempts/{id}
```

## 3. Modelo de dados

```
 teachers 1---N exams 1---N questions 1---N options
                  |              ^             ^  (is_correct: exatamente 1 por questão,
                  |              |             |   garantido na aplicação)
                  |              |             |
                  1              |             |
                  |              |             |
                  N              |             |
 students 1---N exam_attempts    |             |
                 UNIQUE(exam_id, student_id)   |
                 score, total_questions,       |
                 percentage, submitted_at      |
                  |                            |
                  1---N attempt_answers -------+
                        (question_id, option_id, is_correct)
                        UNIQUE(exam_attempt_id, question_id)

 Todas as FKs usam ON DELETE CASCADE.
```

## 4. Fluxos de dados críticos

### F1: Professor cria/edita/exclui prova

```
ExamFormView --POST/PUT /exams--> EnsureProfile(teacher)
  -> (show/PUT/DELETE) EnsureExamOwner: prova de outro professor --> 403
  -> ExamRequest: title, questions[>=1], options[>=2], exatamente 1 is_correct
                                                   (422 se a validação falhar)
  -> ExamService.create/update  [DB::transaction]
       update: trava a linha da prova (lockForUpdate)
               prova já tem tentativas? --sim--> 409 ExamHasAttempts
                                                 (nada é alterado)
       -> ExamRepository
            create: insere exam + syncQuestions (order = índice + 1)
            update: atualiza exam, APAGA todas as questions e recria
  -> DashboardService.flushCache()  (a prova e o título aparecem nas métricas)
  <- ExamResource (201 / 200)

ExamListView --DELETE /exams/{id}--> ExamService.delete
  -> apaga a prova (tentativas saem em CASCADE)
  -> DashboardService.flushCache()
  <- 204

Front: "Editar" fica desabilitado quando attempts_count > 0; a tela de
edição (GET /exams/{id} expõe attempts_count) avisa e bloqueia o "Salvar".
```

### F2: Aluno responde a prova (fluxo central)

```
ExamTakeView
  | GET /student/exams/{id}  -> StudentExamResource (SEM is_correct)
  |
  | POST /student/exams/{id}/attempts  {answers:[{question_id, option_id}]}
  v
EnsureProfile(student) -> SubmitAttemptRequest (ids existem)
  v
ExamAttemptService.submit
  1. já existe tentativa?  --sim--> 409 ExamAlreadyAttempted
  2. prova sem questões?   --------> 422
  3. para cada questão:
       resposta ausente?               -> 422
       alternativa de outra questão?   -> 422
       is_correct? score++
  4. percentage = round(score / total * 100, 2)
  5. DB::transaction: trava a linha da prova + exam_attempt + attempt_answers
     violação do UNIQUE (envio concorrente) --> 409 ExamAlreadyAttempted
  6. DashboardService.flushCache()   (Redis tag "dashboard")
  v
201 AttemptResultResource (score, %, gabarito por questão)
  -> router.push(ResultView)
```

### F3: Dashboard com cache

```
DashboardView
  | GET /dashboard/summary
  | GET /dashboard/exams                 (métricas por prova; alimenta o filtro)
  | GET /dashboard/students?page&per_page (aluno × média)
  | GET /dashboard/ranking?exam_id&page&per_page (per_page limitado a 1..50)
  |     exam_id: não inteiro -> 422, inexistente -> 404, de outro professor -> 403
  v
DashboardService.remember("dashboard:<teacher>:<key>", TTL 300s, tag "dashboard")
   HIT  -> Redis
   MISS -> DashboardRepository (tudo restrito às provas do professor:
                                exam_id IN (SELECT id FROM exams WHERE teacher_id = ?))
             averagePercentage / totalAttempts
             best  = ORDER BY percentage DESC, score DESC, submitted_at ASC
             worst = ORDER BY percentage ASC ...
             ranking = mesma ordenação canônica, paginada, filtro opcional por prova
             examMetrics = exams + COUNT/AVG/MAX/MIN das tentativas (null sem tentativas)
             studentAverages = GROUP BY aluno: COUNT, AVG; ordem: média desc, nome
           -> converte para DTO (array serializável; diferença = média do aluno
              - média geral) -> Redis
Invalidação: ExamAttemptService.submit (nova tentativa),
             ExamService.create/update (provas e títulos listados nas métricas) e
             ExamService.delete (tentativas removidas em cascata)
```

### F4: Identidade (sem login)

```
HomeView -> escolhe perfil -> session.enter(role, id, name) -> localStorage
Toda requisição: Axios adiciona os headers X-User-Role e X-User-Id
EnsureProfile: role diferente -> 403; id malformado ou inexistente -> 401
Rotas: {exam}/{attempt} fora do formato de id -> 404 (nunca chega ao banco)
Posse do recurso: AttemptController verifica attempt.student_id == aluno (403);
                  EnsureExamOwner verifica exam.teacher_id == professor (403)
```

## 5. Rastreabilidade: requisito do teste e onde ele é garantido

| Requisito | Onde é garantido |
|---|---|
| Professor cria provas com múltiplas alternativas | `ExamController` + `ExamRequest` (`options min:2`) |
| Exatamente 1 alternativa correta | `ExamRequest::withValidator` (apenas na aplicação, sem constraint no banco) |
| Aluno vê e realiza provas | `StudentExamController` + `Student*Resource` sem gabarito |
| Uma tentativa por prova | Service (`existsForStudentAndExam`, retorna 409) + `UNIQUE(exam_id, student_id)` (violação concorrente também vira 409) |
| Prova armazenada | Postgres (exams/questions/options, tentativas e respostas); prova respondida não pode ser editada (409) |
| Correção automática | `ExamAttemptService::submit`, no servidor |
| Pontuação e percentual | `exam_attempts.score/percentage` + `ResultView` |
| Dashboard: média, Top 1, ranking paginado | `DashboardService` / `DashboardRepository` / `DashboardView` |
| Dashboard: média por prova, aluno × média, ranking filtrável | `GET /dashboard/exams`, `/dashboard/students`, `/dashboard/ranking?exam_id` + tabelas e filtro no `DashboardView` |
| Professor só gerencia e mede as próprias provas | `ExamRepository::forTeacherWithCounts`, `EnsureExamOwner` (403), dashboard restrito por `teacher_id` com cache por professor |
| Redis para cache | Tag `dashboard`, TTL 300s, invalidação na submissão e ao criar, editar ou excluir prova |
| Docker Compose | 5 serviços + entrypoint automatizado; modo padrão otimizado e modo dev opcional |
| API REST organizada | `apiResource` + prefixo `/student` + Swagger |
| Validação e tratamento de erros | FormRequests, 401/403/404/409/422 em JSON; ids inválidos nunca geram 500 |
| Front consome a API | Vue + Axios |
| 2 acessos sem login | Headers + `EnsureProfile` (simplificação consciente, documentada no README) |

**Decisões que existem por causa do enunciado (não são dívida técnica):**

- O perfil vem por header e pode ser falsificado; não há autenticação real.
- Qualquer professor pode editar qualquer prova (não há checagem de `teacher_id`).
- O dashboard é global e não separa por professor.

## 6. Pontos de atenção (resolvidos)

Riscos encontrados na análise de arquitetura e corrigidos pela change OpenSpec
`fix-exam-integrity-and-cache` (arquivada em
`openspec/changes/archive/2026-09-29-fix-exam-integrity-and-cache/`; requisitos
em `openspec/specs/exam-management` e `openspec/specs/exam-attempts`):

| Severidade | Ponto | Correção |
|---|---|---|
| Alta | Editar uma prova que já tem tentativas apagava em cascata as respostas dos alunos (`ExamRepository::update` recria as questões). | `ExamService::update` responde **409** (`ExamHasAttemptsException`) quando há tentativas e trava a linha da prova, serializando com a submissão. O front desabilita "Editar" e bloqueia o "Salvar". |
| Média | `ExamService::delete` não invalidava o cache: o dashboard mostrava tentativas apagadas por até 5 min. | `delete` chama `DashboardService::flushCache()`. |
| Baixa | Envio duplo simultâneo recebia 500 (`QueryException` do índice único) em vez de 409. | `ExamAttemptService::submit` converte `UniqueConstraintViolationException` em `ExamAlreadyAttemptedException` (409). |
| Baixa | `Cache::tags` dependia do driver: o padrão em `config/cache.php` era `database` (sem tags). | O padrão passou a ser `redis`. |

Limitação aceita: uma prova já respondida não pode ser corrigida; a saída é
excluir e recriar a prova.
