## ADDED Requirements

### Requirement: Média das provas no resumo
`GET /api/dashboard/summary` SHALL retornar, além de `average_percentage` (média de todas as tentativas, cada tentativa com o mesmo peso), o campo `exams_average_percentage`: a média das médias de cada prova do professor que tem ao menos uma tentativa, com o mesmo peso para cada prova. Sem tentativas, o valor MUST ser 0. Os valores MUST ser arredondados em duas casas decimais.

#### Scenario: Provas com números diferentes de tentativas
- **WHEN** a prova A do professor tem três tentativas com 40% e a prova B tem uma tentativa com 90%
- **THEN** `average_percentage` é 52,5 (média por tentativa) e `exams_average_percentage` é 65 (média de 40 e 90)

#### Scenario: Prova sem tentativas não entra na média das provas
- **WHEN** o professor tem uma prova com média 80% e outra prova sem tentativas
- **THEN** `exams_average_percentage` é 80

## MODIFIED Requirements

### Requirement: Aluno × média
O sistema SHALL expor `GET /api/dashboard/students`, paginado com os parâmetros `page` e `per_page` (padrão 10, máximo 50) e os metadados `current_page`, `last_page`, `per_page` e `total`. Cada linha representa um aluno que fez ao menos uma prova do professor e traz: `student_id`, `student_name`, `attempts_count`, `average_percentage` (média do aluno nas provas do professor) e `difference_from_exam_average`: para cada tentativa do aluno, o percentual dele menos a média daquela prova; o campo é a média desses desvios, em pontos percentuais, podendo ser negativa. A comparação MUST ser feita prova a prova, para que a dificuldade de cada prova não distorça o desempenho relativo. Valores MUST ser arredondados em duas casas decimais. As linhas MUST vir ordenadas pelo maior `difference_from_exam_average`; em empate, pela maior média e depois pelo nome do aluno.

#### Scenario: Média de cada aluno comparada à média geral
- **WHEN** o aluno Ana tem tentativas com 100% e 80% e o aluno Bruno tem uma tentativa com 30% nas provas do professor (média geral por tentativa de 70%)
- **THEN** Ana tem `average_percentage` 90 e Bruno 30, e nenhum dos dois recebe um desvio calculado contra a média geral: a resposta não tem mais o campo `difference_from_average`, só `difference_from_exam_average`

#### Scenario: Aluno comparado à média de cada prova
- **WHEN** a prova X do professor tem as tentativas de Ana (100%) e de Bruno (30%), com média 65%, e a prova Y tem só a tentativa de Ana (80%), com média 80%
- **THEN** Ana aparece primeiro com `attempts_count` 2, `average_percentage` 90 e `difference_from_exam_average` 17,5 (média de +35 e 0)
- **AND** Bruno aparece em seguida com `attempts_count` 1, `average_percentage` 30 e `difference_from_exam_average` -35

#### Scenario: Melhor aluno de uma prova difícil não aparece abaixo da média
- **WHEN** a prova difícil tem as tentativas de Carla (60%) e de Davi (20%), média 40%, e a prova fácil tem as tentativas de Davi (100%) e de Eva (100%), média 100%
- **THEN** Carla tem `difference_from_exam_average` 20, positivo, embora sua média (60%) seja menor que a média geral por tentativa (70%)

#### Scenario: Aluno sem tentativas não aparece
- **WHEN** um aluno não fez nenhuma prova do professor
- **THEN** ele não aparece em `GET /api/dashboard/students`

### Requirement: Dashboard na interface do professor
A tela de Dashboard SHALL exibir cartões de resumo com a média das provas e a média por tentativa identificadas de forma distinta, o melhor (Top 1), o pior e o total de tentativas; uma tabela de métricas por prova; uma tabela aluno × média (com paginação e o desvio em relação à média de cada prova destacado como positivo ou negativo); e o ranking. A tela SHALL oferecer um seletor de prova, com a opção "Todas as provas", que recarrega o ranking a partir da primeira página usando o filtro escolhido.

#### Scenario: Filtrar o ranking pela interface
- **WHEN** o professor escolhe uma prova no seletor do ranking
- **THEN** o ranking é recarregado na página 1 mostrando só as tentativas daquela prova, e a paginação passa a respeitar o filtro

#### Scenario: Voltar a todas as provas
- **WHEN** o professor escolhe "Todas as provas" no seletor
- **THEN** o ranking volta a mostrar tentativas de todas as provas dele

#### Scenario: Sem dados
- **WHEN** o professor ainda não tem tentativas
- **THEN** as tabelas exibem uma mensagem de lista vazia em vez de linhas
