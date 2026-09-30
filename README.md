# 🔥 Fênix Provas Online

Aplicação fullstack de **provas online**: professores criam provas de múltipla
escolha, alunos respondem e recebem **correção automática**, e um **dashboard**
mostra o desempenho da turma (média, melhor pontuação e ranking).

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
| Testes | PHPUnit (~94% de cobertura) |
| Documentação | Swagger / OpenAPI (l5-swagger) |

---

## Como rodar

Pré-requisito: **Docker** e **Docker Compose**. Não é preciso ter PHP, Node,
Postgres ou Redis instalados.

```bash
docker compose up --build
```

Na subida, o container do backend automaticamente:
1. instala as dependências (`composer install`);
2. cria o `.env` e gera a `APP_KEY` (se faltarem);
3. aguarda o PostgreSQL ficar pronto;
4. roda as migrations e o seeder (`migrate --seed`);
5. gera a documentação Swagger;
6. sobe o `php-fpm`.

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
| Frontend | 5173 | 5173 |
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
Na tela inicial escolhe-se o perfil; internamente cada requisição às áreas
protegidas envia os headers:

- `X-User-Role`: `teacher` ou `student`
- `X-User-Id`: id do perfil

Os ids disponíveis vêm de `GET /api/teachers` e `GET /api/students` (populados
pelo seeder). Veja também a seção *Decisões técnicas* sobre segurança.

---

## Arquitetura

Duas aplicações independentes conversando por API REST (JSON).

> Mapa completo do sistema (componentes, modelo de dados, fluxos críticos e
> rastreabilidade dos requisitos): [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

### Backend — camadas (evitando *fat controllers*)

```
Rota → Middleware (perfil) → Controller → Form Request (validação)
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
que injeta os headers de perfil). Telas de professor (provas + dashboard) e
aluno (responder + resultado).

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
| GET/POST | `/api/exams` | professor |
| GET/PUT/DELETE | `/api/exams/{exam}` | professor |
| GET | `/api/dashboard/summary` | professor |
| GET | `/api/dashboard/ranking?page&per_page` | professor |
| GET | `/api/student/exams` | aluno |
| GET | `/api/student/exams/{exam}` | aluno |
| POST | `/api/student/exams/{exam}/attempts` | aluno |
| GET | `/api/student/attempts/{attempt}` | aluno |

Respostas padronizadas e tratamento de erros: `422` (validação), `404` (não
encontrado), `409` (tentativa duplicada ou edição de prova já respondida),
`403`/`401` (perfil).

> Uma prova que já tem tentativas **não pode ser editada** (`PUT` responde `409`):
> recriar as questões apagaria as respostas dos alunos. Para corrigi-la, exclua e
> recrie a prova.

---

## Cache (Redis)

O dashboard (leitura pesada e agregações) é cacheado no Redis com **tags**. Ao
registrar uma nova tentativa ou excluir uma prova, o cache do dashboard é
**invalidado**, então as métricas nunca ficam desatualizadas.

---

## Testes

Suíte com **~94% de cobertura**, isolada em SQLite em memória + cache array
(não toca o Postgres/Redis reais).

```bash
# rodar os testes
docker compose exec app php artisan test

# com relatório de cobertura
docker compose exec app php artisan test --coverage
```

Cobrem: CRUD de provas e validações, fluxo do aluno (responder, correção,
tentativa única, resultado), autorização por perfil e por dono do recurso, e o
dashboard (métricas, ranking e invalidação de cache).

---

## Utilidades

```bash
# recriar o banco do zero com dados de exemplo
docker compose exec app php artisan migrate:fresh --seed

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
- **DTO no cache do ranking**: cacheamos uma estrutura serializável (linhas +
  meta), nunca o objeto paginador do Laravel (que não sobrevive à
  (des)serialização no cache).
- **Correção no servidor**: a alternativa correta nunca vai para o cliente antes
  da submissão; a correção e a pontuação são calculadas no backend.

## Próximos passos (fora do escopo atual)

- Autenticação real com **Sanctum** (token verificado no servidor).
- Performance no Docker/Windows: **OPcache** e `vendor/` em volume dedicado.
