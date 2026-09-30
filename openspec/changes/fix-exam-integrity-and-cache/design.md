# Design

## Context

O estado atual está mapeado em `docs/ARCHITECTURE.md` (seções 4 e 6), e a motivação está em `proposal.md`. Pontos do código que moldam a solução:

- `ExamRepository::update` apaga e recria todas as questões. Como as FKs de `attempt_answers` usam `ON DELETE CASCADE`, as respostas dos alunos somem junto.
- O padrão de erro de negócio já existe: `ExamAlreadyAttemptedException` tem `render()` próprio e devolve 409 em JSON.
- `DashboardService::flushCache()` é chamado só em `ExamAttemptService::submit`.
- `ExamAttemptService::submit` verifica (`existsForStudentAndExam`) e depois grava. O índice `UNIQUE(exam_id, student_id)` já protege os dados, mas a violação vira uma `QueryException`, que responde 500.
- `config/cache.php` usa `database` como padrão, e esse store não suporta `Cache::tags`.
- Os testes usam SQLite em memória e cache `array` (forçados no `phpunit.xml`).

## Goals / Non-Goals

**Goals:**
- Cumprir as specs `exam-management` e `exam-attempts` com mudanças pequenas, dentro das camadas atuais (Controller → Service → Repository).
- Cobrir cada ajuste com teste automatizado e verificar o fluxo completo no Docker.

**Non-Goals:**
- Versionar provas ou permitir edição parcial de prova já respondida.
- Checar dono da prova (`teacher_id`) ou implementar autenticação real, que são simplificações do enunciado.
- Trocar a estratégia de "apagar e recriar questões" em provas sem tentativas.

## Decisions

### D1: Bloqueio de edição no Service, com exception de domínio
Criar `App\Exceptions\ExamHasAttemptsException`, no mesmo formato de `ExamAlreadyAttemptedException` (`render()` → 409, `{"message": "Esta prova já foi respondida e não pode ser editada."}`). `ExamService::update` verifica se existe tentativa antes de chamar o repositório.
- *Por que no Service:* é regra de negócio, fica testável sem HTTP e mantém o controller fino.
- *Alternativa descartada:* checar no `ExamRequest`. Isso misturaria validação de formato com estado do banco e retornaria 422 em vez de 409.
- A ordem resultante é: FormRequest (422) → Service (409), como define a spec.

### D2: Serializar edição e submissão pela linha da prova
Dentro da transação de `ExamService::update`, a prova é recarregada com `lockForUpdate()` antes de checar as tentativas. Em `ExamAttemptService::submit`, a transação de gravação também trava a linha da prova (`lockForUpdate()`). Assim uma submissão não consegue entrar entre a checagem e o `delete` das questões.
- *Alternativa descartada:* checar só fora da transação. Seria mais simples, mas deixa uma janela para a mesma perda de dados que motivou a change.
- No SQLite dos testes o lock é ignorado. A cobertura da regra vem dos testes de 409; o lock é uma defesa para o Postgres.

### D3: Exclusão invalida o cache
Injetar `DashboardService` em `ExamService` e chamar `flushCache()` depois de `delete`, no mesmo formato de `submit`. Não há dependência circular, porque `DashboardService` depende só do seu repositório.

### D4: Violação de unicidade vira 409
Em `ExamAttemptService::submit`, envolver a transação de gravação em `try/catch (Illuminate\Database\UniqueConstraintViolationException)` e relançar como `ExamAlreadyAttemptedException`. A verificação prévia continua para o caso comum (sequencial) e o índice único passa a ser a garantia final.
- *Alternativa descartada:* tratar no handler global de exceptions. Isso transformaria em 409 qualquer violação de unicidade da aplicação, inclusive as que não têm a ver com tentativas.
- Teste: teste unitário com o repositório mockado lançando `UniqueConstraintViolationException` em `createWithAnswers`, esperando `ExamAlreadyAttemptedException`.

### D5: Frontend
- `ExamListView`: se `attempts_count > 0`, o botão "Editar" fica `disabled` e ganha `title` explicando o motivo.
- `ExamService::find` passa a carregar `loadCount('attempts')`, então `GET /exams/{id}` expõe `attempts_count` (o `ExamResource` já usa `whenCounted`). Com isso, `ExamFormView` em modo edição mostra um aviso e desabilita "Salvar" quando a prova tem tentativas, cobrindo o acesso direto pela URL.
- `ExamFormView`: no `catch`, um 409 exibe `e.response.data.message`.

### D6: Store de cache padrão
Mudar `'default' => env('CACHE_STORE', 'redis')` em `config/cache.php`. O `phpunit.xml` continua forçando `array`, e o compose já define `redis`.

### D7: Verificação do fluxo completo
Além do `php artisan test`, subir `docker compose up --build` e rodar o roteiro abaixo via `curl` contra `http://localhost:8080/api`: perfis → criar prova → aluno lista/abre (sem gabarito) → submete (201, nota e %) → reenvia (409) → dashboard → editar prova respondida (409) → excluir prova → dashboard sem a prova. Por fim, um passe manual na SPA (`:5173`) pelos dois perfis. O roteiro fica documentado no `tasks.md` e não vira script versionado.

## Risks / Trade-offs

- [Mudança de contrato: `PUT` passa a retornar 409] → Documentar no Swagger e no README. O único consumidor é o front, que é atualizado na mesma change.
- [Professor não consegue corrigir um erro de digitação em prova já respondida] → Trade-off aceito e escolhido explicitamente. A saída é excluir e recriar a prova, ou uma edição parcial futura.
- [`lockForUpdate` não é exercitado nos testes (SQLite)] → A cobertura da regra fica nos testes de 409, e o fluxo no Docker (Postgres) confirma que nada quebra com o lock.
- [Nome da classe `UniqueConstraintViolationException` depende da versão do Laravel] → O projeto usa Laravel 12, onde a classe existe desde a 10.x. Conferir no `vendor` durante a implementação.

## Migration Plan

Sem migrations. O deploy é o rebuild normal (`docker compose up --build`). Rollback: reverter o commit. Nenhum dado é alterado pela change.
