# Spec Delta

## Purpose

Define como o aluno submete uma prova e recebe a correção automática, garantindo uma única tentativa por aluno e prova mesmo sob envios simultâneos.

## ADDED Requirements

### Requirement: Tentativa única por aluno e prova
O sistema MUST aceitar no máximo uma tentativa por par (aluno, prova). Toda submissão além da primeira MUST ser rejeitada com HTTP 409 e uma mensagem em JSON, inclusive quando duas submissões chegam ao mesmo tempo. O sistema MUST NOT responder 500 nesse caso.

#### Scenario: Segunda submissão sequencial
- **WHEN** o aluno já tem uma tentativa registrada para a prova e envia `POST /api/student/exams/{exam}/attempts` novamente
- **THEN** a API responde 409 com a mensagem "Este aluno já realizou esta prova."

#### Scenario: Submissões concorrentes
- **WHEN** duas submissões do mesmo aluno para a mesma prova passam pela verificação prévia ao mesmo tempo e a segunda esbarra na restrição de unicidade ao gravar
- **THEN** a segunda submissão recebe 409 com a mesma mensagem, e não 500
- **AND** existe exatamente uma tentativa gravada para o par (aluno, prova), com as respectivas respostas

### Requirement: Nova tentativa atualiza o dashboard
Após uma submissão aceita, o sistema MUST refletir a nova tentativa nas métricas do dashboard já na próxima consulta.

#### Scenario: Dashboard após submissão
- **WHEN** o dashboard está em cache e um aluno submete uma prova com sucesso
- **THEN** a próxima consulta ao dashboard inclui a nova tentativa na média, no total e no ranking
