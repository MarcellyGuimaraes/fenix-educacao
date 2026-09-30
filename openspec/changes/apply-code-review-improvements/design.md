# Design

## Context

Motivação em `proposal.md` (Why); requisitos em `specs/`. Estado atual relevante:

- `EnsureProfile` faz `Teacher::find($id)` / `Student::find($id)` com o valor cru do header `X-User-Id`. No PostgreSQL, `WHERE id = 'abc'` (ou um número maior que `bigint`) gera `QueryException` → 500. No SQLite dos testes o mesmo valor apenas não encontra nada (401/404), por isso a suíte nunca pegou o problema.
- Os parâmetros `{exam}` e `{attempt}` usam route model binding implícito, sem restrição de formato — mesmo problema.
- `ExamRepository::allWithCounts()` e todas as consultas de `DashboardRepository` são globais; o `teacher_id` é gravado em `store`, mas nunca lido.
- `ExamRequest::authorize()` retorna `true`; a validação do `FormRequest` roda antes do corpo do controller, então uma checagem de dono feita no controller viria depois do 422.
- O dashboard usa `Cache::tags('dashboard')` com chaves globais (`dashboard:summary`, `dashboard:ranking:{per}:{page}`), invalidadas por `flushCache()` ao submeter tentativa e ao excluir prova.
- Exceções de domínio seguem o padrão `render()` próprio (`ExamHasAttemptsException` → 409).

## Goals / Non-Goals

**Goals:**
- Nenhum identificador vindo do cliente chega ao banco sem ter formato de inteiro válido.
- Uma única regra de "dono da prova", aplicada da mesma forma em show/update/delete e no filtro do ranking.
- Novas métricas calculadas no banco (agregações SQL), não em PHP, e cacheadas como DTO serializável.

**Non-Goals:**
- Autenticação real (continua por headers, como pede o enunciado).
- Restringir o que o **aluno** vê (a listagem do aluno continua mostrando todas as provas).
- Invalidação de cache por professor (ver Decisão 6).
- Índices novos ou migrações: `exams.teacher_id` já tem FK e as tabelas são pequenas.

## Decisions

### 1. Restringir o formato dos ids na rota, não tratar a exceção do banco
Registrar `Route::pattern('exam', ...)` e `Route::pattern('attempt', ...)` com a expressão `[1-9][0-9]{0,17}` (até 18 dígitos, sempre abaixo do limite de `bigint`, 9.223.372.036.854.775.807). A rota simplesmente não casa → `NotFoundHttpException` → 404 em JSON pelo `shouldRenderJsonWhen` já existente.
- *Alternativa descartada:* capturar `QueryException` e converter em 404/401. Depende do driver, mascara erros reais de banco e continua mandando a query inválida.
- *Alternativa descartada:* `whereNumber()`. Aceita `0` e números de qualquer tamanho, então o estouro de `bigint` ainda daria 500.

A expressão fica numa constante única (por exemplo `App\Support\Identifier::PATTERN`), usada pelas rotas e pelo middleware.

### 2. Validar `X-User-Id` no `EnsureProfile` antes do `find`
Se o header não casar com a mesma expressão, o middleware aborta com 401 e a mesma mensagem de perfil inexistente ("Professor não identificado." / "Aluno não identificado."). Não há motivo para distinguir, para o cliente, "id malformado" de "id que não existe".

### 3. Verificação de dono num middleware `exam.owner`
Novo middleware aplicado a `show`, `update` e `destroy` de `exams`. Ele lê a prova já resolvida pelo binding (o `SubstituteBindings` do grupo `api` roda antes dos middlewares de rota) e o professor que o `profile:teacher` deixou em `request->attributes`, e aborta com 403 "Esta prova pertence a outro professor." quando `exam.teacher_id !== teacher.id`. O teste de igualdade fica em `Exam::isOwnedBy(Teacher)`, reutilizado pelo filtro do ranking.
- Por ser middleware, roda antes da resolução do `ExamRequest` → 403 vem antes do 422 e antes do 409, como pede o spec.
- *Alternativa descartada:* `ExamRequest::authorize()`. Resolveria só o `update`; `show`/`destroy` não usam o FormRequest, e a regra ficaria espalhada.
- *Alternativa descartada:* Policy/Gate. Exige um usuário autenticado (`Auth::user()`), que o projeto não tem; adaptar isso é mais código do que o problema pede.

### 4. Listagem filtrada no repositório
`allWithCounts()` passa a receber o id do professor (`forTeacherWithCounts(int $teacherId)` no contrato) e aplica `where('teacher_id', ...)`. O `ExamService::list()` recebe o `Teacher` do controller.

### 5. Dashboard com escopo do professor
Todos os métodos do `DashboardRepositoryInterface` recebem `int $teacherId` (e o ranking também `?int $examId`). Uma query base privada restringe as tentativas por `whereIn('exam_id', Exam::select('id')->where('teacher_id', $teacherId))`, reaproveitada por média, total, melhor, pior e ranking. Novos métodos:
- `examMetrics(teacherId)`: `Exam::where('teacher_id')->withCount('attempts')->withAvg/withMax/withMin('attempts', 'percentage')->orderBy('title')` — uma query, e as provas sem tentativas vêm naturalmente com `null`.
- `studentAverages(teacherId, perPage, page)`: tentativas da query base agrupadas por `student_id`, com `COUNT`/`AVG`, `JOIN students` para o nome, `orderByDesc(avg)->orderBy(name)` e `paginate` (o Laravel já conta grupos via subquery).

O `DashboardService` monta os DTOs, arredonda (`round(..., 2)`, com cast para `float` porque o PostgreSQL devolve `numeric` como string) e calcula `difference_from_average` = média do aluno − `averagePercentage(teacherId)`.

O controller valida `exam_id` com `['nullable', 'integer', 'min:1']` (422, e `integer` recusa valores maiores que `PHP_INT_MAX`), carrega a prova (404 se não existir) e aplica `isOwnedBy` (403).

### 6. Cache: chave por professor, invalidação global
As chaves passam a embutir o professor e o filtro: `dashboard:{teacher}:summary`, `dashboard:{teacher}:exams`, `dashboard:{teacher}:students:{per}:{page}`, `dashboard:{teacher}:ranking:{exam|all}:{per}:{page}`. A tag continua única (`dashboard`), e `flushCache()` continua limpando tudo.
- *Alternativa descartada:* uma tag por professor, com flush direcionado. Economiza recomputar o dashboard de outros professores, mas exige saber o dono da prova em cada ponto de invalidação. Com poucos professores e TTL de 5 min, a limpeza global é mais simples e já está coberta pelos specs existentes.

### 7. Frontend
`DashboardView.vue` carrega em paralelo `summary`, `exams`, `students` e `ranking`. A lista de `dashboard/exams` alimenta tanto a tabela "Média por prova" quanto o `<select>` do ranking (primeira opção "Todas as provas"), então não precisa chamar `/exams` de novo. Trocar o filtro chama `loadRanking(1)` com `exam_id`, e a paginação passa a carregar o filtro ativo. A tabela aluno × média tem paginação própria, e a diferença aparece com sinal e com classe positiva/negativa. `ExamFormView`/`ExamListView` exibem a `message` da API quando recebem 403.

## Risks / Trade-offs

- [A suíte roda em SQLite, que não reproduz o 500 do PostgreSQL] → Os testes validam o contrato (404/401 para ids inválidos), o que já cobre a regressão, porque a query nunca é executada. Além disso, a verificação manual via `docker compose` (PostgreSQL) entra nas tarefas.
- [Mudança de contrato (BREAKING): o frontend ou outros clientes deixam de ver provas e métricas de outros professores] → Intencional. O seeder cria um único professor ("Prof. Ana Souza"), dono de todas as provas, então a demonstração continua com os mesmos dados; um segundo professor com uma prova própria é acrescentado ao seeder para dar para ver o isolamento. O README é atualizado.
- [Flush global do cache recalcula o dashboard de todos os professores] → Custo baixo neste volume; dá para migrar para tags por professor depois, sem mudar nenhum spec.
- [`AVG` sobre `decimal` tem precisão e tipo diferentes entre SQLite e PostgreSQL] → Cast para `float` e `round(…, 2)` no service; os testes comparam com `assertEquals`.
- [Limitar a 18 dígitos recusa ids válidos entre 10^18 e 2^63] → Irrelevante na prática (ids sequenciais), e mantém a expressão simples e segura.

## Migration Plan

Sem migração de banco. Backend e frontend sobem juntos no mesmo deploy (`docker compose up --build`). Rollback: reverter o commit. Nenhum dado é alterado, e o cache é limpo automaticamente pelo TTL ou pelo próximo flush.
