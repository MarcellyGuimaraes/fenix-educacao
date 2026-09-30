# exam-management Specification

## Purpose
Define como o professor gerencia provas (criar, editar, excluir) sem comprometer as tentativas dos alunos já registradas nem a consistência das métricas do dashboard.

## Requirements

### Requirement: Prova com tentativas não pode ser editada
O sistema MUST rejeitar a edição de uma prova que possui ao menos uma tentativa registrada, respondendo com HTTP 409 e uma mensagem em JSON, sem alterar a prova, suas questões, suas alternativas ou as respostas já registradas.

#### Scenario: Edição de prova já respondida
- **WHEN** o professor envia `PUT /api/exams/{exam}` com um payload válido para uma prova que tem ao menos uma tentativa
- **THEN** a API responde 409 com um campo `message` explicando que provas já respondidas não podem ser editadas
- **AND** o título, as questões e as alternativas da prova permanecem inalterados
- **AND** o resultado da tentativa existente continua listando todas as respostas do aluno

#### Scenario: Edição de prova sem tentativas
- **WHEN** o professor envia `PUT /api/exams/{exam}` com um payload válido para uma prova sem tentativas
- **THEN** a API responde 200 e a prova passa a refletir o novo conteúdo

#### Scenario: Payload inválido em prova já respondida
- **WHEN** o professor envia um payload inválido para uma prova que tem tentativas
- **THEN** a API responde 422 com os erros de validação (a validação de entrada vem antes da checagem de tentativas) e nada é alterado

### Requirement: Interface sinaliza prova não editável
A interface do professor SHALL impedir que o professor inicie a edição de uma prova com tentativas e SHALL exibir o motivo; se a API ainda assim responder 409, a interface SHALL mostrar a mensagem retornada.

#### Scenario: Listagem com prova já respondida
- **WHEN** o professor abre a lista de provas e uma prova tem tentativas
- **THEN** a ação "Editar" dessa prova aparece desabilitada, com a indicação de que provas já respondidas não podem ser editadas

#### Scenario: Conflito recebido ao salvar
- **WHEN** o professor salva a edição e a API responde 409
- **THEN** a tela de edição exibe a mensagem de erro e não navega para a listagem

### Requirement: Exclusão de prova reflete imediatamente no dashboard
Ao excluir uma prova, o sistema MUST remover suas tentativas das métricas do dashboard (média, melhor, pior, total e ranking) já na próxima consulta, sem esperar a expiração do cache.

#### Scenario: Dashboard após excluir prova respondida
- **WHEN** o dashboard foi consultado (e está em cache), e em seguida o professor exclui uma prova que tinha tentativas
- **THEN** a próxima consulta a `GET /api/dashboard/summary` e a `GET /api/dashboard/ranking` não inclui nenhuma tentativa da prova excluída
