# Tasks

> Verificação padrão: `npm run build` em `frontend/` sem erros + conferência manual no navegador (`docker compose -f docker-compose.dev.yml up` ou `npm run dev`), nos temas claro e escuro.

## 1. Fundação visual (tokens, tema, fonte)

- [x] 1.1 Reescrever `frontend/src/style.css` com tokens primitivos, tokens semânticos (claro em `:root`, escuro em `:root[data-theme="dark"]` e em `@media (prefers-color-scheme: dark) :root:not([data-theme="light"])`), `color-scheme`, foco visível (`:focus-visible` com `--focus-ring`), `prefers-reduced-motion` e utilitários existentes (`.btn`, `.card`, `.row`, `.grid`, `.muted`, `.spacer`, `table`, `.alert`) sobre os tokens; verificar que o app atual continua renderizando igual ou melhor após `npm run build`
- [x] 1.2 Atualizar `frontend/index.html`: `lang="pt-BR"`, `meta theme-color`, fonte Inter (Google Fonts, `display=swap`) e script inline que aplica `data-theme` salvo em `localStorage` (com `try/catch`) antes do bundle; verificar que recarregar com tema "Escuro" salvo não pisca o tema claro
- [x] 1.3 Criar `frontend/src/composables/useTheme.js` (modos `light`/`dark`/`system`, persistência em `localStorage` com `try/catch`, reação à mudança de `prefers-color-scheme` no modo sistema); verificar pelos cenários de tema da spec
- [x] 1.4 Remover assets sem uso do template Vite (`src/assets/vite.svg`, `vue.svg`, `hero.png`, `public/icons.svg` se não referenciados) e trocar `public/favicon.svg` por um ícone de chama da marca; verificar com busca por referências e `npm run build`

## 2. Componentes base

- [x] 2.1 Criar `src/components/ui/AppIcon.vue` com o conjunto de ícones SVG usados (chama, sair, sol, lua, monitor, mais, lápis, lixeira, check, x, alerta, info, livro, usuário, troféu, seta cima/baixo, chevrons, gráfico, inbox); verificar que renderiza com `currentColor` e `aria-hidden` por padrão
- [x] 2.2 Criar `AppButton.vue` (variantes primary/secondary/ghost/danger, tamanhos, `loading` com spinner, slot de ícone, `aria-label` para botão só-ícone), `AppCard.vue` e `AppBadge.vue` (tons neutral/primary/success/danger/info); verificar contraste dos badges nos dois temas
- [x] 2.3 Criar `PageHeader.vue`, `EmptyState.vue`, `ErrorState.vue` (mensagem + "Tentar novamente" emitindo `retry`), `SkeletonBlock.vue` (respeitando `prefers-reduced-motion`) e `AppPagination.vue`; verificar usando-os em uma view no grupo 4
- [x] 2.4 Criar `ProgressBar.vue` (com `role="progressbar"` e `aria-valuenow/min/max`) e `ScoreRing.vue` (SVG circular proporcional ao percentual, com texto acessível); verificar valores 0%, 50% e 100%
- [x] 2.5 Criar `ThemeToggle.vue` (Claro/Escuro/Sistema, operável por teclado, rótulo acessível) usando `useTheme`; verificar os três modos

## 3. Serviços globais de feedback

- [x] 3.1 Criar `src/composables/useToast.js` (estado singleton; `success`/`error`/`info`; sucesso expira em ~4 s, erro persiste até fechar) e `src/components/ToastHost.vue` (`aria-live="polite"`, erros com `role="alert"`, botão fechar com `aria-label`); verificar que um toast disparado antes de `router.push` continua visível na tela seguinte
- [x] 3.2 Criar `src/composables/useConfirm.js` (`confirm(opts) → Promise<boolean>`) e `src/components/ConfirmDialog.vue` com `<dialog>` + `showModal()`, Esc/"Cancelar"/clique no backdrop resolvendo `false` e devolução de foco ao elemento de origem; verificar pelos cenários "Cancelar exclusão" e navegação por teclado
- [x] 3.3 Montar `ToastHost` e `ConfirmDialog` uma única vez em `App.vue`; verificar que não há `window.confirm`/`window.alert` restantes com `grep -rn "confirm(\|alert(" frontend/src` (apenas `useConfirm`)

## 4. Shell e tela inicial

- [x] 4.1 Redesenhar `App.vue`: topbar com marca (ícone de chama), navegação com rota ativa destacada, `ThemeToggle`, avatar com iniciais + nome + papel e botão "Sair" com ícone; em < 640 px navegação em segunda linha; verificar a 360 px sem rolagem horizontal
- [x] 4.2 Redesenhar `HomeView.vue`: hero com marca, dois cartões de perfil (ícones SVG em vez de emojis), seletor, botão, `ThemeToggle` visível, skeleton durante o carregamento e `ErrorState` com "Tentar novamente" se a API falhar; verificar com a API parada e no ar

## 5. Experiência do aluno

- [x] 5.1 Redesenhar `student/ExamListView.vue` com `PageHeader`, cartões de prova (questões, badge "Concluída · N%"), `SkeletonBlock`, `EmptyState` e `ErrorState`; verificar lista vazia, com provas e com prova concluída
- [x] 5.2 Redesenhar `student/ExamTakeView.vue`: `ProgressBar` sticky "X de N respondidas", alternativas com letra (A, B, C…), área inteira clicável, radio visualmente oculto mas focável, estado selecionado com ícone + borda + fundo, rodapé com "Faltam N questões" e envio via `useConfirm`, toast de erro para 409/422; verificar cenários "Progresso atualizado", "Envio bloqueado" e "Confirmar envio da prova", inclusive só com teclado
- [x] 5.3 Redesenhar `student/ResultView.vue` com `ScoreRing`, acertos/total, mensagem por faixa e detalhamento com `AppBadge` "Correta"/"Incorreta" + ícone e respostas escolhida/correta; verificar cenário "Questão incorreta"

## 6. Telas do professor

- [x] 6.1 Redesenhar `teacher/ExamListView.vue` com `PageHeader` + ação "Nova prova", cartões com badges de questões/tentativas e motivo de bloqueio visível, botões Editar/Excluir com ícone, `EmptyState` com ação "Nova prova", exclusão via `useConfirm` e resultado via `useToast` (mensagem da API em 403/409, recarregando a lista no 409); verificar cenários "Confirmar exclusão", "Exclusão bloqueada pela API" e "Lista de provas do professor vazia" e que o requisito de bloqueio de `exam-management` segue atendido
- [x] 6.2 Redesenhar `teacher/ExamFormView.vue`: `PageHeader`, cartões numerados por questão, seleção da alternativa correta destacada, remover questão/alternativa com botões só-ícone com `aria-label`, erros de validação junto aos campos com os tokens, barra de ações fixa, alerta de prova bloqueada/proibida com os novos estilos e toast de sucesso antes de voltar à lista; verificar criar, editar, erro 422, 403 e 409
- [x] 6.3 Redesenhar `teacher/DashboardView.vue` mantendo todas as seções e dados: cartões de métrica com ícone, tabelas com os novos estilos, desvio positivo/negativo com seta + cor + sinal, destaque para posições 1–3 do ranking, filtro de prova estilizado, `AppPagination`, skeleton e `ErrorState`; verificar que o requisito de tela do `teacher-dashboard` continua atendido e que as tabelas rolam dentro do cartão a 360 px

## 7. Documentação e verificação integrada

- [x] 7.1 Atualizar a seção Frontend do `README.md` e `docs/ARCHITECTURE.md` descrevendo tokens/tema, componentes base e os serviços de confirmação/toast; verificar que os caminhos citados existem
- [x] 7.2 Verificação integrada: `npm run build` sem erros; percorrer os fluxos completos de professor (criar, editar, excluir, dashboard) e aluno (listar, responder, resultado) nos temas claro, escuro e sistema, a 360 px e em desktop, só com teclado; conferir contraste AA dos pares texto/fundo principais e ausência de cores hex fora de `style.css` (`grep -rn "#[0-9a-fA-F]\{3,6\}" frontend/src --include=*.vue`)
