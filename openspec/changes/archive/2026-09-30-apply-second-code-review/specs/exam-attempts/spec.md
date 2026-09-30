## ADDED Requirements

### Requirement: Correção serializada com a edição da prova
A submissão MUST travar a prova, verificar se o aluno já tem tentativa, ler as questões e alternativas e fazer a correção dentro da mesma transação em que grava a tentativa, de modo que uma edição concorrente da prova não possa trocar as questões entre a leitura e a gravação. O sistema MUST NOT responder 500 por causa de uma edição concorrente.

#### Scenario: Edição concorrente antes da gravação
- **WHEN** o professor edita a prova (substituindo as questões) enquanto um aluno envia as respostas baseadas na versão anterior
- **THEN** ou a edição termina antes e a submissão é corrigida contra as questões novas, respondendo 422 porque as respostas não pertencem às questões atuais, ou a submissão termina antes e a edição responde 409; em nenhum caso a API responde 500
