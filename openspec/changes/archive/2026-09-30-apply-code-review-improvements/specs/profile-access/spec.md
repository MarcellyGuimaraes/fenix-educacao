## Purpose

Define como a API identifica o perfil que está agindo (professor ou aluno) pelos headers e como trata identificadores inválidos, em headers ou em parâmetros de rota, garantindo respostas 4xx previsíveis e nunca um erro 500.

## ADDED Requirements

### Requirement: Header de identificação inválido é tratado como perfil não identificado
O sistema MUST aceitar como `X-User-Id` apenas um inteiro positivo, escrito só com dígitos e dentro do intervalo de um inteiro de 64 bits com sinal. Qualquer outro valor (vazio, com letras, sinais, espaços, decimais ou grande demais) MUST ser tratado como perfil inexistente: HTTP 401 com uma mensagem em JSON, sem consultar o banco com o valor recebido. O sistema MUST NOT responder 500 nesse caso.

#### Scenario: Professor com id não numérico
- **WHEN** uma requisição à área do professor envia `X-User-Role: teacher` e `X-User-Id: abc`
- **THEN** a API responde 401 com a mensagem "Professor não identificado."

#### Scenario: Aluno com id não numérico
- **WHEN** uma requisição à área do aluno envia `X-User-Role: student` e `X-User-Id: 1 OR 1=1`
- **THEN** a API responde 401 com a mensagem "Aluno não identificado."

#### Scenario: Id acima do limite de um inteiro
- **WHEN** uma requisição envia `X-User-Id: 99999999999999999999`
- **THEN** a API responde 401, e não 500

#### Scenario: Id válido de perfil existente
- **WHEN** uma requisição envia o papel correto e o id numérico de um perfil existente
- **THEN** a requisição segue normalmente para a ação solicitada

### Requirement: Parâmetro de rota inválido responde 404
O sistema MUST responder 404 com uma mensagem em JSON quando o identificador de prova (`{exam}`) ou de tentativa (`{attempt}`) na URL não for um inteiro positivo dentro do intervalo de um inteiro de 64 bits. O sistema MUST NOT responder 500 nesse caso, em nenhuma das áreas (professor ou aluno).

#### Scenario: Prova com id não numérico na área do professor
- **WHEN** o professor envia `GET`, `PUT` ou `DELETE` para `/api/exams/abc`
- **THEN** a API responde 404

#### Scenario: Prova com id não numérico na área do aluno
- **WHEN** o aluno envia `GET /api/student/exams/abc` ou `POST /api/student/exams/abc/attempts`
- **THEN** a API responde 404

#### Scenario: Tentativa com id não numérico
- **WHEN** o aluno envia `GET /api/student/attempts/abc`
- **THEN** a API responde 404

#### Scenario: Id numérico acima do limite
- **WHEN** qualquer rota com `{exam}` ou `{attempt}` recebe `99999999999999999999`
- **THEN** a API responde 404, e não 500
