# Proposal

## Why

Um code review do projeto apontou três pontos de melhoria, por prioridade: (1) a API responde 500 quando recebe ids não numéricos (em rota ou no header `X-User-Id`), porque o valor chega cru ao PostgreSQL e estoura um erro de sintaxe de inteiro; (2) o dashboard só tem métricas globais, sem visão por prova, sem a média de cada aluno e sem filtro no ranking; (3) qualquer professor lista, vê, edita e exclui provas de outros professores, pois o `teacher_id` nunca é conferido. Os três são baratos de corrigir e afetam diretamente a robustez e a coerência do sistema.

## What Changes

- Ids não numéricos (ou fora do intervalo de um inteiro) passam a ser rejeitados antes de chegar ao banco:
  - parâmetros de rota `{exam}` e `{attempt}` inválidos → 404;
  - header `X-User-Id` inválido → 401 (mesmo tratamento de um perfil inexistente).
- **BREAKING** `GET /api/exams` passa a listar apenas as provas do professor identificado no header.
- **BREAKING** `GET/PUT/DELETE /api/exams/{exam}` passam a responder 403 quando a prova pertence a outro professor, sem alterar nada.
- **BREAKING** O dashboard (`summary` e `ranking`) passa a considerar apenas tentativas das provas do professor logado; o cache passa a ser separado por professor.
- Novo endpoint `GET /api/dashboard/exams`: média, total de tentativas, melhor e pior percentual por prova.
- Novo endpoint `GET /api/dashboard/students`: média de cada aluno (aluno × média), com o total de tentativas e a diferença em relação à média geral, paginado.
- `GET /api/dashboard/ranking` aceita o filtro opcional `exam_id`.
- A tela de Dashboard do professor ganha a tabela de médias por prova, a tabela aluno × média e um seletor de prova que filtra o ranking.

## Capabilities

### New Capabilities
- `profile-access`: identificação do perfil pelos headers e tratamento de identificadores inválidos (header e parâmetros de rota) sem erro 500.
- `teacher-dashboard`: métricas do dashboard do professor — resumo, métricas por prova, aluno × média e ranking filtrável — restritas às provas do próprio professor.

### Modified Capabilities
- `exam-management`: a listagem passa a ser filtrada pelo professor e visualizar/editar/excluir exigem que o professor seja o dono da prova.

## Impact

- **Backend**: `routes/api.php` (restrição numérica dos parâmetros), `EnsureProfile` (validação do `X-User-Id`), `ExamController`/`ExamService`/`ExamRepository` (filtro e verificação de dono), `DashboardController`/`DashboardService`/`DashboardRepository` e respectivo contrato (escopo por professor, novos endpoints, filtro do ranking), anotações OpenAPI.
- **Frontend**: `views/teacher/DashboardView.vue` (novas tabelas e filtro); `ExamListView`/`ExamFormView` passam a tratar 403.
- **Testes**: novos casos em `ExamManagementTest`, `DashboardTest`, `StudentExamTest` e um teste de perfil/ids inválidos.
- **Compatibilidade**: clientes que dependiam de ver provas ou métricas de outros professores deixam de vê-las (mudança intencional). Não há migração de banco: `exams.teacher_id` já existe.
