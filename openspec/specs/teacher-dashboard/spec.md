# teacher-dashboard Specification

## Purpose
Define as métricas que o dashboard entrega ao professor — resumo geral, métricas por prova, média de cada aluno e ranking filtrável — sempre restritas às tentativas das provas do próprio professor e sempre atualizadas após mudanças nas tentativas.

## Requirements

### Requirement: Dashboard restrito às provas do professor
Todas as métricas do dashboard (resumo, métricas por prova, aluno × média e ranking) MUST considerar apenas as tentativas feitas em provas cujo autor é o professor identificado no header. Tentativas em provas de outros professores MUST NOT aparecer nem influenciar nenhum valor. Os dados em cache de um professor MUST NOT ser servidos a outro professor.

#### Scenario: Resumo ignora provas de outro professor
- **WHEN** o professor A tem uma tentativa com 100% em sua prova e o professor B tem uma tentativa com 0% na prova dele, e o professor A consulta `GET /api/dashboard/summary`
- **THEN** a resposta tem `total_attempts` igual a 1, `average_percentage` igual a 100 e `best` e `worst` referentes apenas à tentativa da prova do professor A

#### Scenario: Cache separado por professor
- **WHEN** o professor A consulta o dashboard (que fica em cache) e em seguida o professor B consulta o mesmo endpoint
- **THEN** o professor B recebe as métricas das próprias provas, e não as do professor A

#### Scenario: Professor sem tentativas
- **WHEN** nenhuma prova do professor tem tentativas
- **THEN** o resumo retorna `total_attempts` 0, `average_percentage` 0 e `best`/`worst` nulos, e o ranking retorna uma lista vazia

### Requirement: Métricas por prova
O sistema SHALL expor `GET /api/dashboard/exams`, que retorna uma linha para cada prova do professor, incluindo as que ainda não têm tentativas, com: `exam_id`, `exam_title`, `attempts_count`, `average_percentage`, `best_percentage` e `worst_percentage`. Para provas sem tentativas, `attempts_count` MUST ser 0 e as três porcentagens MUST ser `null`. As médias MUST ser arredondadas em duas casas decimais. As linhas MUST vir ordenadas pelo título da prova.

#### Scenario: Média por prova
- **WHEN** a prova "Matemática" do professor tem tentativas com 100%, 50% e 0%
- **THEN** a linha dessa prova traz `attempts_count` 3, `average_percentage` 50, `best_percentage` 100 e `worst_percentage` 0

#### Scenario: Prova sem tentativas
- **WHEN** o professor tem uma prova que ninguém respondeu
- **THEN** essa prova aparece com `attempts_count` 0 e porcentagens `null`

### Requirement: Aluno × média
O sistema SHALL expor `GET /api/dashboard/students`, paginado com os parâmetros `page` e `per_page` (padrão 10, máximo 50) e os metadados `current_page`, `last_page`, `per_page` e `total`. Cada linha representa um aluno que fez ao menos uma prova do professor e traz: `student_id`, `student_name`, `attempts_count`, `average_percentage` (média do aluno nas provas do professor) e `difference_from_average` (média do aluno menos a média geral do professor, em pontos percentuais, podendo ser negativa). Valores MUST ser arredondados em duas casas decimais. As linhas MUST vir ordenadas pela maior média; em empate, pelo nome do aluno.

#### Scenario: Média de cada aluno comparada à média geral
- **WHEN** o aluno Ana tem tentativas com 100% e 80% e o aluno Bruno tem uma tentativa com 30% nas provas do professor (média geral 70%)
- **THEN** Ana aparece primeiro com `attempts_count` 2, `average_percentage` 90 e `difference_from_average` 20
- **AND** Bruno aparece em seguida com `attempts_count` 1, `average_percentage` 30 e `difference_from_average` -40

#### Scenario: Aluno sem tentativas não aparece
- **WHEN** um aluno não fez nenhuma prova do professor
- **THEN** ele não aparece em `GET /api/dashboard/students`

### Requirement: Ranking filtrável por prova
`GET /api/dashboard/ranking` SHALL aceitar o parâmetro opcional `exam_id`. Sem o parâmetro, o ranking MUST listar as tentativas de todas as provas do professor; com ele, MUST listar apenas as tentativas daquela prova, mantendo a ordenação (maior percentual, maior acerto, envio mais antigo) e a paginação, com posições recalculadas dentro do filtro. Um `exam_id` que não seja um inteiro positivo MUST resultar em 422. Um `exam_id` de prova inexistente MUST resultar em 404, e de prova de outro professor MUST resultar em 403.

#### Scenario: Ranking filtrado
- **WHEN** o professor tem as provas X e Y com tentativas e consulta `GET /api/dashboard/ranking?exam_id={X}`
- **THEN** a resposta contém apenas tentativas da prova X, a primeira na posição 1, e `meta.total` igual ao número de tentativas da prova X

#### Scenario: Ranking sem filtro
- **WHEN** o professor consulta `GET /api/dashboard/ranking` sem `exam_id`
- **THEN** a resposta contém as tentativas de todas as suas provas

#### Scenario: Filtro inválido
- **WHEN** o professor consulta `GET /api/dashboard/ranking?exam_id=abc`
- **THEN** a API responde 422 com o erro de validação do campo `exam_id`

#### Scenario: Filtro com prova de outro professor
- **WHEN** o professor A consulta o ranking com o `exam_id` de uma prova do professor B
- **THEN** a API responde 403

### Requirement: Dashboard na interface do professor
A tela de Dashboard SHALL exibir, além dos cartões de resumo e do ranking, uma tabela de métricas por prova e uma tabela aluno × média (com paginação e a diferença em relação à média geral destacada como positiva ou negativa). A tela SHALL oferecer um seletor de prova, com a opção "Todas as provas", que recarrega o ranking a partir da primeira página usando o filtro escolhido.

#### Scenario: Filtrar o ranking pela interface
- **WHEN** o professor escolhe uma prova no seletor do ranking
- **THEN** o ranking é recarregado na página 1 mostrando só as tentativas daquela prova, e a paginação passa a respeitar o filtro

#### Scenario: Voltar a todas as provas
- **WHEN** o professor escolhe "Todas as provas" no seletor
- **THEN** o ranking volta a mostrar tentativas de todas as provas dele

#### Scenario: Sem dados
- **WHEN** o professor ainda não tem tentativas
- **THEN** as tabelas exibem uma mensagem de lista vazia em vez de linhas
