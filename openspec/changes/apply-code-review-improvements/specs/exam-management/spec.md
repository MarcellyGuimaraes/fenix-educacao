## ADDED Requirements

### Requirement: Listagem mostra apenas as provas do professor
`GET /api/exams` MUST retornar somente as provas cujo autor é o professor identificado no header, mantendo os contadores de questões e de tentativas e a ordenação atual (mais recentes primeiro).

#### Scenario: Dois professores com provas
- **WHEN** o professor A tem duas provas, o professor B tem uma, e o professor A consulta `GET /api/exams`
- **THEN** a resposta contém exatamente as duas provas do professor A

#### Scenario: Professor sem provas
- **WHEN** um professor que não criou nenhuma prova consulta `GET /api/exams`
- **THEN** a resposta é 200 com `data` vazio

### Requirement: Apenas o dono acessa, edita ou exclui a prova
O sistema MUST responder 403 com uma mensagem em JSON quando um professor tentar visualizar (`GET`), editar (`PUT`) ou excluir (`DELETE`) `/api/exams/{exam}` de uma prova criada por outro professor, sem alterar a prova, suas questões, alternativas, tentativas ou o cache do dashboard. A verificação de dono MUST vir antes da validação do payload e antes da checagem de tentativas: um professor que não é o dono recebe 403 mesmo com payload inválido ou com a prova já respondida. Uma prova inexistente continua respondendo 404.

#### Scenario: Editar prova de outro professor
- **WHEN** o professor A envia `PUT /api/exams/{exam}` com payload válido para uma prova do professor B
- **THEN** a API responde 403 com a mensagem "Esta prova pertence a outro professor."
- **AND** a prova permanece inalterada

#### Scenario: Excluir prova de outro professor
- **WHEN** o professor A envia `DELETE /api/exams/{exam}` para uma prova do professor B
- **THEN** a API responde 403 e a prova continua existindo, com suas tentativas

#### Scenario: Visualizar prova de outro professor
- **WHEN** o professor A envia `GET /api/exams/{exam}` para uma prova do professor B
- **THEN** a API responde 403 e o gabarito não é exposto

#### Scenario: Payload inválido em prova de outro professor
- **WHEN** o professor A envia um payload inválido em `PUT /api/exams/{exam}` para uma prova do professor B
- **THEN** a API responde 403, e não 422

#### Scenario: Dono edita a própria prova
- **WHEN** o professor autor envia `PUT /api/exams/{exam}` com payload válido para uma prova sem tentativas
- **THEN** a API responde 200, como antes

## MODIFIED Requirements

### Requirement: Prova com tentativas não pode ser editada
O sistema MUST rejeitar a edição, feita pelo professor dono, de uma prova que possui ao menos uma tentativa registrada, respondendo com HTTP 409 e uma mensagem em JSON, sem alterar a prova, suas questões, suas alternativas ou as respostas já registradas.

#### Scenario: Edição de prova já respondida
- **WHEN** o professor dono envia `PUT /api/exams/{exam}` com um payload válido para uma prova que tem ao menos uma tentativa
- **THEN** a API responde 409 com um campo `message` explicando que provas já respondidas não podem ser editadas
- **AND** o título, as questões e as alternativas da prova permanecem inalterados
- **AND** o resultado da tentativa existente continua listando todas as respostas do aluno

#### Scenario: Edição de prova sem tentativas
- **WHEN** o professor dono envia `PUT /api/exams/{exam}` com um payload válido para uma prova sem tentativas
- **THEN** a API responde 200 e a prova passa a refletir o novo conteúdo

#### Scenario: Payload inválido em prova já respondida
- **WHEN** o professor dono envia um payload inválido para uma prova que tem tentativas
- **THEN** a API responde 422 com os erros de validação (a validação de entrada vem antes da checagem de tentativas) e nada é alterado
