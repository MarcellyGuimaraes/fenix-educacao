## ADDED Requirements

### Requirement: Prova com tentativas não pode ser excluída
O sistema MUST rejeitar a exclusão, feita pelo professor dono, de uma prova que possui ao menos uma tentativa registrada, respondendo com HTTP 409 e uma mensagem em JSON, sem remover a prova, suas questões ou as tentativas. O banco de dados MUST impedir a remoção de uma prova com tentativas mesmo fora da API. A interface SHALL desabilitar a ação "Excluir" de provas já respondidas e exibir a mensagem retornada se a API ainda assim responder 409.

#### Scenario: Exclusão de prova já respondida
- **WHEN** o professor dono envia `DELETE /api/exams/{exam}` para uma prova com ao menos uma tentativa
- **THEN** a API responde 409 com um campo `message` explicando que provas já respondidas não podem ser excluídas
- **AND** a prova e as tentativas continuam no banco, e o resultado da tentativa continua acessível ao aluno

#### Scenario: Exclusão de prova sem tentativas
- **WHEN** o professor dono envia `DELETE /api/exams/{exam}` para uma prova sem tentativas
- **THEN** a API responde 204 e a prova é removida com suas questões e alternativas

#### Scenario: Listagem com prova já respondida
- **WHEN** o professor abre a lista de provas e uma prova tem tentativas
- **THEN** as ações "Editar" e "Excluir" dessa prova aparecem desabilitadas, com a indicação do motivo

### Requirement: Exclusão de prova sem tentativas reflete no dashboard
Ao excluir uma prova (necessariamente sem tentativas), o sistema MUST remover a prova das métricas por prova e do seletor do ranking já na próxima consulta, sem esperar a expiração do cache.

#### Scenario: Dashboard após excluir prova
- **WHEN** as métricas por prova foram consultadas (e estão em cache), e em seguida o professor exclui uma prova sem tentativas
- **THEN** a próxima consulta a `GET /api/dashboard/exams` não inclui a prova excluída

## REMOVED Requirements

### Requirement: Exclusão de prova reflete imediatamente no dashboard
**Reason**: provas com tentativas não podem mais ser excluídas, então deixa de existir o caso de excluir uma prova respondida e retirar suas tentativas das métricas.
**Migration**: substituído por "Prova com tentativas não pode ser excluída" e "Exclusão de prova sem tentativas reflete no dashboard".
