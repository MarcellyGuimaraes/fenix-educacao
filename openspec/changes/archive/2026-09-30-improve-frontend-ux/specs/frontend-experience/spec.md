# Spec Delta

## Purpose

Define a experiência da interface web do Fênix Provas — tema claro/escuro, feedback ao usuário (confirmações, notificações, carregamento e estados vazios), progresso ao responder uma prova, apresentação do resultado, acessibilidade e responsividade — de forma consistente em todas as telas de professor e aluno.

## ADDED Requirements

### Requirement: Tema claro e escuro
A interface SHALL oferecer tema claro e tema escuro. Na primeira visita, o tema MUST seguir a preferência do sistema operacional (`prefers-color-scheme`). A interface SHALL oferecer um controle, acessível em todas as telas (inclusive a tela inicial), para escolher entre "Claro", "Escuro" e "Sistema". A escolha MUST ser lembrada no navegador entre visitas e MUST ser aplicada antes da primeira pintura, sem piscar o tema errado. Todas as telas MUST ficar legíveis em ambos os temas, sem cores fixas que desapareçam no fundo.

#### Scenario: Primeira visita com sistema em modo escuro
- **WHEN** o usuário abre a aplicação pela primeira vez com o sistema operacional configurado em modo escuro
- **THEN** a interface é exibida no tema escuro

#### Scenario: Escolha manual persistida
- **WHEN** o usuário escolhe "Claro" no controle de tema e recarrega a página
- **THEN** a interface é exibida no tema claro, mesmo que o sistema operacional esteja em modo escuro

#### Scenario: Voltar a seguir o sistema
- **WHEN** o usuário escolhe "Sistema" e o sistema operacional muda de claro para escuro
- **THEN** a interface passa para o tema escuro sem recarregar a página

### Requirement: Confirmação de ações destrutivas em diálogo próprio
A interface MUST NOT usar `window.confirm` nem `window.alert`. Ações destrutivas (excluir prova) e o envio definitivo das respostas do aluno SHALL pedir confirmação em um diálogo modal da própria aplicação, que nomeia o item afetado e oferece as ações "Cancelar" e a ação confirmatória. O diálogo MUST prender o foco enquanto aberto, MUST fechar com a tecla Esc ou com "Cancelar" sem executar a ação, e MUST devolver o foco ao elemento que o abriu ao fechar.

#### Scenario: Cancelar exclusão
- **WHEN** o professor clica em "Excluir" em uma prova e, no diálogo, pressiona Esc
- **THEN** o diálogo fecha, a prova não é excluída e o foco volta ao botão "Excluir"

#### Scenario: Confirmar exclusão
- **WHEN** o professor confirma a exclusão no diálogo
- **THEN** a prova é excluída, a lista é recarregada e uma notificação de sucesso é exibida

#### Scenario: Confirmar envio da prova
- **WHEN** o aluno, com todas as questões respondidas, clica em "Enviar respostas"
- **THEN** um diálogo informa que as respostas não poderão ser alteradas e só envia após a confirmação

### Requirement: Notificações de resultado de ações
A interface SHALL exibir notificações temporárias (toasts) para o resultado de ações do usuário: sucesso ao criar, salvar ou excluir prova e ao enviar respostas; erro quando a ação falha. Quando a API retorna uma mensagem de erro (por exemplo, 403 ou 409), a notificação MUST exibir essa mensagem. Notificações de sucesso MUST desaparecer sozinhas após alguns segundos; notificações de erro MUST permanecer até serem fechadas pelo usuário ou por novo evento. As notificações MUST ser anunciadas a leitores de tela.

#### Scenario: Exclusão bloqueada pela API
- **WHEN** o professor confirma a exclusão de uma prova e a API responde 409 com uma mensagem
- **THEN** uma notificação de erro exibe a mensagem retornada pela API e a lista de provas é recarregada

#### Scenario: Prova salva
- **WHEN** o professor salva uma prova com sucesso
- **THEN** ele é levado à lista de provas e vê uma notificação de sucesso

### Requirement: Estados de carregamento, vazio e erro
Toda tela que carrega dados SHALL exibir um indicador visual de carregamento com a forma aproximada do conteúdo (skeleton) em vez de apenas texto. Listas e tabelas sem dados SHALL exibir um estado vazio com ícone, mensagem explicativa e, quando fizer sentido, a ação principal (por exemplo, "Nova prova" na lista vazia do professor). Falhas de carregamento SHALL exibir a mensagem de erro com a opção "Tentar novamente".

#### Scenario: Lista de provas do professor vazia
- **WHEN** o professor não tem provas cadastradas
- **THEN** a tela mostra um estado vazio com uma ação para criar a primeira prova

#### Scenario: Falha ao carregar
- **WHEN** a requisição de carregamento da tela falha
- **THEN** a tela mostra a mensagem de erro e um botão "Tentar novamente" que refaz a requisição

### Requirement: Progresso ao responder a prova
Na tela de responder prova, a interface SHALL exibir o progresso de questões respondidas no formato "X de N respondidas" com uma barra proporcional, visível durante a rolagem. Cada alternativa SHALL ser identificada por letra (A, B, C…), MUST ter toda a sua área clicável e MUST indicar claramente o estado selecionado sem depender apenas de cor. As alternativas MUST ser operáveis por teclado. Enquanto houver questões sem resposta, o envio MUST ficar desabilitado e a interface SHALL indicar quantas faltam.

#### Scenario: Progresso atualizado
- **WHEN** o aluno seleciona uma alternativa em uma questão ainda não respondida de uma prova com 5 questões, tendo respondido 2
- **THEN** o indicador passa a mostrar "3 de 5 respondidas" e a barra avança proporcionalmente

#### Scenario: Envio bloqueado com questões pendentes
- **WHEN** restam questões sem resposta
- **THEN** o botão de envio está desabilitado e a interface informa quantas questões faltam

### Requirement: Apresentação do resultado
A tela de resultado SHALL destacar o percentual obtido com um indicador visual proporcional ao percentual e o número de acertos sobre o total. O detalhamento SHALL marcar cada questão como correta ou incorreta com ícone e texto (não só cor) e, nas incorretas, SHALL exibir a alternativa escolhida e a correta.

#### Scenario: Questão incorreta
- **WHEN** o aluno vê o resultado de uma questão que errou
- **THEN** a questão aparece marcada como "Incorreta" com ícone, mostrando a resposta escolhida e a resposta correta

### Requirement: Acessibilidade e responsividade
A interface MUST declarar o idioma `pt-BR`, MUST exibir foco visível em todos os elementos interativos e MUST manter contraste de texto de pelo menos 4,5:1 (WCAG AA) em ambos os temas. Botões compostos só por ícone MUST ter rótulo acessível. Todas as telas MUST funcionar sem rolagem horizontal da página a partir de 360 px de largura; tabelas largas MAY rolar horizontalmente dentro do próprio contêiner. A interface MUST respeitar `prefers-reduced-motion`, desativando animações não essenciais.

#### Scenario: Navegação por teclado
- **WHEN** o usuário navega pela aplicação apenas com Tab e Enter/Espaço
- **THEN** todo elemento interativo recebe foco visível e pode ser acionado

#### Scenario: Tela estreita
- **WHEN** qualquer tela é exibida com 360 px de largura
- **THEN** o conteúdo se reorganiza em uma coluna, sem rolagem horizontal da página
