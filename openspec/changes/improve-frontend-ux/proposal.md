# Proposal

## Why

O frontend cumpre todos os fluxos do teste, mas tem aparência genérica: estilos soltos em `style.css`, cores fixas espalhadas pelas views (`#dcfce7`, `#fee2e2`…), emojis no lugar de ícones, `window.confirm`/`window.alert` nativos para confirmação e erros, estados de carregamento em texto puro e nenhuma noção de progresso ao responder uma prova. Como o sistema é a vitrine do teste técnico, uma interface coesa e agradável melhora a percepção de qualidade sem mexer na API.

## What Changes

- Criar um **sistema visual próprio** baseado em design tokens (cores, tipografia, espaçamento, raios, sombras) em CSS puro, sem novas dependências de UI, eliminando cores fixas nas views.
- Adicionar **modo escuro**: segue `prefers-color-scheme` por padrão, com alternância manual (claro / escuro / sistema) persistida no navegador.
- Criar **componentes base reutilizáveis** (botão, card, badge, estado vazio, skeleton de carregamento, ícones SVG inline, diálogo de confirmação e toasts) e aplicá-los em todas as views.
- **Substituir `window.confirm` e `window.alert`** por um diálogo de confirmação acessível e por notificações (toasts) — por exemplo, na exclusão de prova e nos erros 403/409 retornados pela API; também exibir toast de sucesso ao salvar/excluir prova e ao enviar respostas.
- Redesenhar o **cabeçalho/navegação** (marca, navegação ativa, perfil e ação de sair) e a **tela inicial** de escolha de perfil.
- Melhorar a **experiência do aluno ao responder**: barra de progresso de questões respondidas, alternativas identificadas por letra (A, B, C…) com área clicável ampla e estado selecionado claro, e confirmação antes do envio.
- Melhorar a **tela de resultado**: destaque visual da pontuação (anel/indicador de percentual) e detalhamento de acertos/erros mais legível.
- Polir as telas do professor (lista de provas, formulário e dashboard) com a nova linguagem visual, mantendo todo o conteúdo e comportamento atuais.
- Garantir **acessibilidade básica** (contraste AA, foco visível, navegação por teclado nos diálogos, `lang="pt-BR"`) e **responsividade** até 360 px de largura.

Fora do escopo: gráficos no dashboard, qualquer alteração de API/backend, bibliotecas de componentes ou Tailwind.

## Capabilities

### New Capabilities
- `frontend-experience`: requisitos de experiência da interface web — tema (claro/escuro), feedback ao usuário (diálogo de confirmação, toasts, estados de carregamento e vazios), progresso ao responder a prova, apresentação do resultado, acessibilidade e responsividade.

### Modified Capabilities
<!-- Nenhuma. Os requisitos de interface existentes em `exam-management` e `teacher-dashboard` (bloquear edição/exclusão com motivo, exibir a mensagem da API, conteúdo do dashboard) continuam válidos; apenas a forma de exibição muda (toast/diálogo em vez de alert/confirm), o que continua atendendo aos requisitos como escritos. -->

## Impact

- **Código**: `frontend/src/style.css`, `frontend/src/App.vue`, `frontend/index.html` e todas as views em `frontend/src/views/**`; novos arquivos em `frontend/src/components/` e `frontend/src/composables/` (tema, toasts, confirmação).
- **Dependências**: nenhuma nova (fonte Inter via Google Fonts opcional, com fallback de sistema).
- **API/backend**: sem alterações.
- **Docs**: seção de frontend do `README.md` e `docs/ARCHITECTURE.md` atualizadas para citar o sistema de design e os componentes base.
