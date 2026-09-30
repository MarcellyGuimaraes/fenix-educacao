# Proposal

## Why

A análise de arquitetura (`docs/ARCHITECTURE.md`, seção 6) encontrou quatro riscos no código atual. O mais grave: quando o professor edita uma prova que já foi respondida, as respostas dos alunos são apagadas em cascata, mas a nota continua gravada. Isso quebra a regra do desafio de que "a prova deve ser armazenada". Os outros três deixam o dashboard desatualizado, fazem um envio duplo retornar 500 em vez de 409 e tornam o cache frágil à configuração. Corrigir agora, antes da entrega, evita que um avaliador encontre esses problemas testando o fluxo.

## What Changes

- **BREAKING (API)**: `PUT /api/exams/{exam}` passa a retornar **409** quando a prova já tem ao menos uma tentativa, sem alterar nada.
- Frontend do professor: o botão "Editar" fica desabilitado para provas com tentativas, com o motivo indicado; se o 409 chegar mesmo assim, a tela de edição mostra a mensagem.
- `DELETE /api/exams/{exam}` passa a invalidar o cache do dashboard, e as métricas deixam de mostrar tentativas apagadas.
- Envios simultâneos da mesma prova pelo mesmo aluno sempre resultam em **409** para o envio perdedor, nunca em 500.
- O store de cache padrão da aplicação passa a ser `redis`, para que o cache com tags do dashboard não dependa de uma variável de ambiente.
- Testes automatizados cobrem cada ajuste, e o fluxo completo (professor → aluno → dashboard) é verificado no ambiente Docker.
- `docs/ARCHITECTURE.md` é atualizado para marcar os pontos de atenção como resolvidos.

## Capabilities

### New Capabilities
- `exam-management`: gestão de provas pelo professor, incluindo a proteção das provas que já foram respondidas e o efeito da exclusão nas métricas.
- `exam-attempts`: submissão de tentativas pelo aluno, incluindo a garantia de tentativa única mesmo com envios concorrentes.

### Modified Capabilities
<!-- Nenhuma: o projeto ainda não tem specs em openspec/specs/. -->

## Impact

- **Backend**: `ExamService` / `ExamController` (update e delete), `ExamAttemptService` / `ExamAttemptRepository` (tratamento da violação de unicidade), `config/cache.php`, uma exception nova para o 409 de edição.
- **Frontend**: `views/teacher/ExamListView.vue`, `views/teacher/ExamFormView.vue`.
- **API/Docs**: resposta 409 documentada no Swagger do `PUT /exams/{exam}`; `docs/ARCHITECTURE.md` e a tabela de erros do `README.md`.
- **Testes**: `tests/Feature/Teacher/ExamManagementTest.php`, `tests/Feature/DashboardTest.php`, `tests/Feature/Student/StudentExamTest.php` e/ou `tests/Unit/ExamAttemptServiceTest.php`.
- Sem migrations e sem dependências novas.
