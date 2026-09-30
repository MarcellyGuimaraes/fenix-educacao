# 🔥 Fênix Provas Online

Aplicação fullstack de **provas online**: professores criam provas de múltipla
escolha, alunos respondem e recebem **correção automática**, e um **dashboard**
mostra o desempenho da turma (média geral e por prova, melhor e pior pontuação,
média de cada aluno e ranking filtrável por prova). Cada professor vê apenas as
próprias provas e métricas.

Projeto do desafio técnico **Fênix – Desenvolvedor**.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4 + Laravel 12 (API REST) |
| Frontend | Vue 3 + Vite |
| Banco | PostgreSQL 16 |
| Cache | Redis 7 |
| Ambiente | Docker / Docker Compose |
| Testes | PHPUnit (~96% de cobertura) |
| Documentação | Swagger / OpenAPI (l5-swagger) |

---

## Como rodar

Pré-requisito: **Docker** e **Docker Compose**. Não é preciso ter PHP, Node,
Postgres ou Redis instalados.

```bash
docker compose up --build
```

Esse é o **modo padrão**, otimizado para avaliação: o código do backend e o
`vendor/` vão dentro da imagem (sem bind mounts), o OPcache não revalida
arquivos, a config/rotas/eventos ficam em cache, `APP_DEBUG=false` e o frontend
é um build estático (`vite build`) servido por nginx.

No build da imagem do backend roda o `composer install`. Na subida, o container
automaticamente:
1. cria o `.env` e gera a `APP_KEY` (se faltarem);
2. aguarda o PostgreSQL ficar pronto;
3. roda as migrations e o seeder (`migrate --seed`, idempotente);
4. gera a documentação Swagger;
5. faz o cache de config, rotas, eventos e views (`php artisan optimize`);
6. sobe o `php-fpm`.

> No modo padrão, alterações no código só entram com um novo
> `docker compose up --build`.

### Modo de desenvolvimento (opcional)

Para editar com reload, suba com o override de desenvolvimento:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

Nesse modo o código de `backend/` e `frontend/` é montado do host (alterações
refletem sem rebuild), o `vendor/` fica num volume nomeado (`backend_vendor`), o
OPcache revalida os arquivos, `APP_DEBUG=true`, sem cache de config, e o frontend
roda no Vite dev server com HMR.

> Se o `composer.lock` mudar, recrie o volume do `vendor/`:
> `docker compose -f docker-compose.yml -f docker-compose.dev.yml down` e
> `docker volume rm fenix-educacao_backend_vendor`.

### URL da API no frontend

No modo padrão, `VITE_API_URL` é um **argumento de build** do frontend (fica
fixado no bundle). Para trocar, defina a variável no `.env` da raiz e refaça o
build: `docker compose up --build frontend`.

### Performance

Medido no Docker Desktop para Windows, 10 chamadas aquecidas por endpoint
(mediana, com `curl`):

| Endpoint | Antes (bind mount) | Modo padrão | Modo dev |
|---|---|---|---|
| `GET /api/teachers` | ~4,7 s | ~41 ms | ~215 ms |
| `GET /api/student/exams` | ~5,8 s | ~49 ms | ~304 ms |
| `GET /api/dashboard/summary` | ~7,3 s | ~43 ms | ~234 ms |

A lentidão anterior vinha do bind mount do código (inclusive `vendor/`) no
Docker Desktop do Windows: cada leitura de arquivo cruzava a fronteira Windows ↔
VM. No modo padrão o código fica na imagem; no modo dev só o `vendor/` sai do
bind mount.

### Acessos

| Serviço | URL |
|---|---|
| Frontend (SPA) | http://localhost:5173 |
| API | http://localhost:8080/api |
| Documentação (Swagger UI) | http://localhost:8080/api/documentation |
| OpenAPI (JSON) | http://localhost:8080/docs |

### Portas no host

| Serviço | Host | Container |
|---|---|---|
| Frontend | 5173 | 80 (nginx; 5173 no modo dev) |
| API (nginx) | 8080 | 80 |
| PostgreSQL | **5434** | 5432 |
| Redis | 6379 | 6379 |

> A porta de host do Postgres é **5434** para não colidir com um Postgres local
> na 5432. Todas as portas são configuráveis via `.env` (veja `.env.example`).

Para parar:

```bash
docker compose down
```

---

## Perfis e acesso

Conforme o enunciado, **não há login**: existem dois acessos (Professor e Aluno).
Na tela inicial escolhe-se o perfil e, em cada um, quem está acessando (há um
seletor de professor e um de aluno); internamente cada requisição às áreas
protegidas envia os headers:

- `X-User-Role`: `teacher` ou `student`
- `X-User-Id`: id do perfil

Os ids disponíveis vêm de `GET /api/teachers` e `GET /api/students` (populados
pelo seeder). Veja também a seção *Decisões técnicas* sobre segurança.

Dados de exemplo do seeder:

| Perfil | Quem | Provas |
|---|---|---|
| Professor | Prof. Ana Souza | Conhecimentos Gerais, Lógica de Programação |
| Professor | Prof. Bruno Costa | História do Brasil |
| Aluno | João Silva, Maria Oliveira, Pedro Santos, Carla Lima, Lucas Rocha | — |

> Entrar como cada professor mostra o isolamento: um não vê as provas nem as
> métricas do outro.

---

## Arquitetura

Duas aplicações independentes conversando por API REST (JSON).

> Mapa completo do sistema (componentes, modelo de dados, fluxos críticos e
> rastreabilidade dos requisitos): [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

### Backend — camadas (evitando *fat controllers*)

```
Rota → Middleware (perfil, dono da prova) → Controller → Form Request (validação)
     → Service (regra de negócio) → Repository (acesso a dados) → Model
     ← API Resource (serialização de saída)
```

- **Controllers** finos: só orquestram.
- **Form Requests**: validação de entrada (ex.: exatamente uma alternativa
  correta por questão).
- **Services**: regra de negócio (correção automática, tentativa única, cálculo
  do dashboard, invalidação de cache) — dentro de transações onde necessário.
- **Repositories** (contrato + implementação Eloquent): acesso a dados,
  injetados por interface (facilita troca/testes).
- **API Resources**: formato de saída; o resource do aluno **nunca expõe o
  gabarito**.

### Frontend

Vue 3 + Vue Router (com guarda de perfil) + Pinia (sessão) + Axios (interceptor
que injeta os headers de perfil). Telas de professor (provas + dashboard com
resumo, média por prova, aluno × média e ranking com filtro de prova) e aluno
(responder + resultado).

---

## Modelagem

```
teachers                      students
──────────                    ──────────
exams (teacher_id)            exam_attempts (exam_id, student_id)
 └─ questions (exam_id)          UNIQUE(exam_id, student_id)  ← 1 tentativa/prova
     └─ options (question_id)    └─ attempt_answers (exam_attempt_id, question_id, option_id)
        is_correct
```

- `Exam` 1—N `Question` 1—N `Option` (uma correta por questão).
- `ExamAttempt` guarda `score`, `total_questions`, `percentage` já calculados.
- Índice **único** `(exam_id, student_id)` garante no banco a regra de uma
  tentativa por prova.

---

## API

Principais endpoints (detalhes e schemas no Swagger):

| Método | Rota | Perfil |
|---|---|---|
| GET | `/api/teachers`, `/api/students` | público |
| GET/POST | `/api/exams` (lista só as provas do professor) | professor |
| GET/PUT/DELETE | `/api/exams/{exam}` (só o autor da prova) | professor |
| GET | `/api/dashboard/summary` | professor |
| GET | `/api/dashboard/exams` (média, melhor, pior e total por prova) | professor |
| GET | `/api/dashboard/students?page&per_page` (aluno × média) | professor |
| GET | `/api/dashboard/ranking?exam_id&page&per_page` | professor |
| GET | `/api/student/exams` | aluno |
| GET | `/api/student/exams/{exam}` | aluno |
| POST | `/api/student/exams/{exam}/attempts` | aluno |
| GET | `/api/student/attempts/{attempt}` | aluno |

Respostas padronizadas e tratamento de erros: `422` (validação), `404` (não
encontrado), `409` (tentativa duplicada ou edição de prova já respondida),
`403`/`401` (perfil).

> **Cada professor enxerga apenas o que é seu.** A listagem de provas e todas as
> métricas do dashboard consideram só as provas do professor identificado no
> header. Visualizar, editar ou excluir a prova de outro professor responde
> `403` (antes de qualquer validação), assim como filtrar o ranking por ela.
>
> **Ids inválidos nunca geram `500`.** Um `X-User-Id` que não seja um inteiro
> positivo responde `401`; um `{exam}`/`{attempt}` inválido na URL (letras, zero,
> número grande demais) responde `404`. O valor nem chega ao banco.

> Uma prova que já tem tentativas **não pode ser editada** (`PUT` responde `409`):
> recriar as questões apagaria as respostas dos alunos. Para corrigi-la, exclua e
> recrie a prova.

---

## Cache (Redis)

O dashboard (leitura pesada e agregações) é cacheado no Redis com **tags**, com
chaves separadas por professor (o cache de um nunca é servido a outro). Ao
registrar uma nova tentativa ou criar, editar ou excluir uma prova, o cache do
dashboard é **invalidado**, então as métricas nunca ficam desatualizadas.

---

## Testes

Suíte com **~96% de cobertura**, isolada em SQLite em memória + cache array
(não toca o Postgres/Redis reais). Roda no container padrão mesmo com a config
em cache: o `phpunit.xml` aponta os caches para arquivos que nunca são gerados.

```bash
# rodar os testes
docker compose exec app php artisan test

# com relatório de cobertura (o PCOV fica desligado por padrão e é ligado só aqui)
docker compose exec -e PHP_PCOV_ENABLED=1 app php artisan test --coverage
```

Cobrem: CRUD de provas e validações, fluxo do aluno (responder, correção,
tentativa única, resultado), autorização por perfil e por dono do recurso
(inclusive dono da prova), ids inválidos (401/404, nunca 500) e o dashboard
(métricas gerais e por prova, aluno × média, ranking com filtro, isolamento
entre professores e invalidação de cache).

---

## Utilidades

```bash
# recriar o banco do zero com dados de exemplo
# (--force: o modo padrão roda com APP_ENV=production)
docker compose exec app php artisan migrate:fresh --seed --force

# limpar o cache
docker compose exec app php artisan cache:clear
```

---

## Decisões técnicas

- **Camadas (Service/Repository/Resource)**: mantêm o controller fino e a regra
  de negócio testável e isolada do framework.
- **Perfil por header, sem login**: o enunciado dispensa autenticação. A
  identidade é resolvida em **um único ponto** (middleware `EnsureProfile`), que
  disponibiliza o professor/aluno já resolvido para a requisição. Isso deixa a
  troca por autenticação real (ex.: **Laravel Sanctum** com token) localizada:
  muda-se o middleware + um endpoint de sessão, sem tocar controllers/services.
  > ⚠️ Headers são falsificáveis — não é seguro para produção; é uma
  > simplificação consciente do escopo do desafio.
- **Dono da prova num middleware** (`exam.owner`): roda antes da validação do
  Form Request, então quem não é o dono recebe `403` e nunca um `422`/`409` que
  revelaria detalhes da prova. O mesmo teste (`Exam::isOwnedBy`) protege o
  filtro do ranking.
- **Ids validados antes do banco**: `{exam}`/`{attempt}` têm restrição de formato
  na rota e o `X-User-Id` é validado no middleware (`App\Support\Identifier`).
  Capturar o erro do banco dependeria do driver (o SQLite dos testes nem falha)
  e mascararia erros reais.
- **Métricas agregadas no banco**: média por prova e aluno × média saem de
  `COUNT`/`AVG`/`MAX`/`MIN` em uma query cada, não de laços em PHP.
- **Cache por professor, invalidação global**: as chaves incluem o professor,
  mas uma nova tentativa ou qualquer escrita em prova limpa a tag `dashboard` inteira. É mais
  simples que uma tag por professor e barato neste volume (TTL de 5 min).
- **DTO no cache do ranking**: cacheamos uma estrutura serializável (linhas +
  meta), nunca o objeto paginador do Laravel (que não sobrevive à
  (des)serialização no cache).
- **Correção no servidor**: a alternativa correta nunca vai para o cliente antes
  da submissão; a correção e a pontuação são calculadas no backend.

## Próximos passos (fora do escopo atual)

- Autenticação real com **Sanctum** (token verificado no servidor).
