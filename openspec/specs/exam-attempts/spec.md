# exam-attempts Specification

## Purpose
Define como o aluno submete uma prova e recebe a correção automática, garantindo uma única tentativa por aluno e prova mesmo sob envios simultâneos.

## Requirements

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

### Requirement: Correção serializada com a edição da prova
A submissão MUST travar a prova, verificar se o aluno já tem tentativa, ler as questões e alternativas e fazer a correção dentro da mesma transação em que grava a tentativa, de modo que uma edição concorrente da prova não possa trocar as questões entre a leitura e a gravação. O sistema MUST NOT responder 500 por causa de uma edição concorrente.

#### Scenario: Edição concorrente antes da gravação
- **WHEN** o professor edita a prova (substituindo as questões) enquanto um aluno envia as respostas baseadas na versão anterior
- **THEN** ou a edição termina antes e a submissão é corrigida contra as questões novas, respondendo 422 porque as respostas não pertencem às questões atuais, ou a submissão termina antes e a edição responde 409; em nenhum caso a API responde 500
