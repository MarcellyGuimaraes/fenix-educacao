# Spec Delta

## Purpose

Define como a aplicação sobe e se comporta no ambiente Docker: execução em um único comando, tempo de resposta aceitável para avaliação, modo de desenvolvimento separado e testes executáveis dentro do container.

## ADDED Requirements

### Requirement: Execução em um único comando
A aplicação completa (API, frontend, PostgreSQL e Redis) MUST subir com `docker compose up --build` a partir de um clone limpo do repositório, sem PHP, Node, Postgres ou Redis instalados no host e sem passos manuais adicionais, já com o banco migrado e populado e a documentação Swagger gerada.

#### Scenario: Subida a partir de um clone limpo
- **WHEN** o avaliador roda `docker compose up --build` num clone limpo
- **THEN** o frontend responde em `http://localhost:5173`, a API em `http://localhost:8080/api`, e a Swagger UI em `http://localhost:8080/api/documentation`
- **AND** `GET /api/teachers` e `GET /api/students` retornam os perfis do seeder

#### Scenario: Reinício sem duplicar dados
- **WHEN** o ambiente é derrubado com `docker compose down` e sobe de novo sem remover volumes
- **THEN** os dados existentes são preservados e o seeder não duplica professores, alunos nem provas

### Requirement: Tempo de resposta da API
No ambiente padrão, depois da primeira requisição (container aquecido), as requisições de leitura da API MUST responder em menos de 500 ms cada, medidas no host com `curl`, inclusive no Docker Desktop para Windows.

#### Scenario: Leituras aquecidas
- **WHEN** `GET /api/teachers`, `GET /api/student/exams` e `GET /api/dashboard/summary` são chamados 10 vezes cada, após uma requisição de aquecimento
- **THEN** todas as chamadas respondem com sucesso em menos de 500 ms

#### Scenario: Frontend servido pronto para produção
- **WHEN** o navegador abre `http://localhost:5173`
- **THEN** a página é servida a partir de arquivos estáticos minificados (sem servidor de desenvolvimento)
- **AND** acessar diretamente uma rota da SPA (por exemplo, `/professor/dashboard`) devolve a aplicação, e não 404

### Requirement: Erros não expõem detalhes internos
No ambiente padrão, respostas de erro 5xx da API MUST NOT incluir stack trace, caminhos de arquivo nem mensagens de exceção internas. Os formatos de erro 4xx existentes (401, 403, 404, 409, 422) MUST permanecer inalterados.

#### Scenario: Erro de validação continua detalhado
- **WHEN** um professor envia uma prova inválida
- **THEN** a API responde 422 com `message` e `errors` por campo, como antes

#### Scenario: Erro interno sem detalhes
- **WHEN** ocorre uma exceção não tratada durante uma requisição da API
- **THEN** a resposta é 500 com uma mensagem genérica, sem `trace`, `file` ou `line`

### Requirement: Modo de desenvolvimento opcional
O repositório MUST oferecer um modo de desenvolvimento, ativado explicitamente, em que alterações no código do backend e do frontend são refletidas sem rebuild da imagem.

#### Scenario: Edição com o modo dev ativo
- **WHEN** o desenvolvedor sobe com `docker compose -f docker-compose.yml -f docker-compose.dev.yml up` e altera um arquivo em `backend/app` ou `frontend/src`
- **THEN** a próxima requisição à API reflete a alteração do backend e o frontend recarrega com a alteração, sem `--build`

### Requirement: Testes executáveis no container padrão
A suíte de testes MUST rodar dentro do container da API no ambiente padrão, isolada do PostgreSQL e do Redis reais, inclusive com a configuração da aplicação em cache. O relatório de cobertura MUST continuar disponível.

#### Scenario: Suíte no ambiente padrão
- **WHEN** o avaliador roda `docker compose exec app php artisan test`
- **THEN** todos os testes passam usando o banco e o cache de teste, sem tocar os dados do Postgres/Redis do ambiente

#### Scenario: Cobertura
- **WHEN** o avaliador roda o comando de cobertura documentado no README
- **THEN** o relatório de cobertura é exibido
