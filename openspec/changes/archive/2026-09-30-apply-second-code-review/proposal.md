# Proposal

## Why

Um segundo code review, mais crítico, apontou cinco problemas que um avaliador técnico encontraria:

1. **Comparação injusta em "Aluno × média"**: a média do aluno é comparada com a média geral de todas as tentativas, misturando provas de dificuldades diferentes. Um aluno que fez só a prova difícil e foi o melhor nela aparece "abaixo da média".
2. **"Média das provas" ausente**: o resumo só traz a média por tentativa (ponderada pelo número de tentativas de cada prova). O enunciado pede a "média das provas", e a interface não deixa clara a diferença.
3. **Exclusão apaga o histórico**: a edição de prova respondida é bloqueada (409) porque apagaria as respostas, mas a exclusão faz o mesmo em cascata, contrariando o requisito de armazenar o histórico de tentativas.
4. **Race condition na submissão**: as questões são lidas e a prova é corrigida fora da transação, antes do `lockForUpdate`. Uma edição concorrente pode apagar as questões nesse intervalo, e a gravação das respostas estoura uma violação de FK (500).
5. **Demo vazia**: o seed não cria tentativas, então quem avalia abre um dashboard vazio.

## What Changes

- **BREAKING** `GET /api/dashboard/students`: o campo `difference_from_average` é substituído por `difference_from_exam_average` (média dos desvios do aluno em relação à média de cada prova que ele fez); a ordenação passa a ser por esse desvio.
- `GET /api/dashboard/summary` ganha `exams_average_percentage` (média das médias de cada prova), mantendo `average_percentage` (média por tentativa).
- **BREAKING** `DELETE /api/exams/{exam}` responde 409 quando a prova tem tentativas; o banco passa a restringir (`restrictOnDelete`) a exclusão de prova com tentativas.
- A submissão trava a prova, verifica a tentativa existente, lê as questões e corrige **dentro** da transação.
- O seed cria tentativas de exemplo.
- Frontend: cartões "Média das provas" e "Média por tentativa", coluna de desvio por prova e ação "Excluir" desabilitada para prova respondida.

## Capabilities

### Modified Capabilities
- `teacher-dashboard`: média das provas no resumo e comparação do aluno prova a prova.
- `exam-management`: exclusão de prova respondida bloqueada.
- `exam-attempts`: correção serializada com edições concorrentes.

## Impact

- **Backend**: `DashboardRepository`/`DashboardService`, `ExamService`, `ExamHasAttemptsException`, `ExamAttemptService`, migration nova (FK restritiva), `DatabaseSeeder`, OpenAPI.
- **Frontend**: `DashboardView`, `ExamListView`.
- **Testes**: ajustes em `DashboardTest` e `ExamManagementTest`.
- **Fora do escopo**: associar provas a alunos/turmas, testes unitários adicionais, refatorações de camadas e ajustes de desempenho apontados no review.
