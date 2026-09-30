# Design

## Context

O frontend é uma SPA Vue 3 + Vite + Pinia + Vue Router + Axios, sem biblioteca de UI. Hoje:

- `src/style.css` define poucas variáveis (`--primary`, `--bg`, `--surface`…) e classes utilitárias (`.btn`, `.card`, `.alert`, `.row`, `.grid`); as views usam cores fixas (`#dcfce7`, `#fee2e2`, `#fff7ed`, `#dbeafe`) e estilos inline (`style="margin-top: 1rem"`).
- Não existe pasta `components/`: cada view repete markup de cabeçalho de página, cartões, badges, paginação e mensagens de carregamento ("Carregando…").
- `teacher/ExamListView.vue` usa `confirm()` e `alert()`; o envio da prova do aluno não tem confirmação.
- `index.html` declara `lang="en"` e não carrega fonte (a `Inter` citada no CSS só aparece se instalada).
- O projeto não tem testes de frontend; a verificação é via build (`npm run build`) e uso manual.

Motivação e escopo: ver `proposal.md`. Requisitos: ver `specs/frontend-experience/spec.md`.

## Goals / Non-Goals

**Goals:**
- Uma única fonte de verdade visual (tokens CSS) que troca de tema só redefinindo variáveis.
- Componentes base pequenos, sem dependências, usados por todas as views.
- Serviços globais de confirmação e toast chamáveis de qualquer view por composable.
- Manter rotas, store de sessão, chamadas à API e toda a lógica de negócio das views intactas.

**Non-Goals:**
- Gráficos no dashboard, i18n, testes automatizados de frontend, refatoração da lógica das views, mudanças no backend.

## Decisions

### D1. CSS próprio com design tokens em camadas
`style.css` passa a ter: (1) tokens primitivos (paleta laranja "fênix" + neutros "slate", escala de espaçamento 4px, raios, sombras, tipografia); (2) tokens semânticos (`--color-bg`, `--color-surface`, `--color-surface-raised`, `--color-text`, `--color-text-muted`, `--color-border`, `--color-primary`, `--color-primary-soft`, `--color-success(-soft)`, `--color-danger(-soft)`, `--color-info(-soft)`, `--focus-ring`); (3) estilos base e utilitários existentes (`.row`, `.grid`, `.muted`, `.spacer`) reescritos sobre os tokens semânticos.
Views e componentes usam **somente** tokens semânticos — nenhuma cor hex fora de `style.css`.
*Alternativas:* Tailwind ou lib de componentes — descartadas pelo usuário (dependência nova, visual genérico, poluição de templates).

### D2. Tema via atributo `data-theme` no `<html>`
Os tokens semânticos do tema escuro ficam em `:root[data-theme="dark"]` e também em `@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { … } }`. Assim "Sistema" = sem atributo, "Claro"/"Escuro" = atributo explícito.
Um script inline mínimo em `index.html` lê `localStorage` (em `try/catch`) e aplica o atributo antes do bundle carregar, evitando flash. O composable `useTheme` (estado reativo + persistência) é consumido por um componente `ThemeToggle` presente no topo das telas autenticadas e na tela inicial. Declarar `color-scheme: light dark` para que controles nativos (select, scrollbar, radio) acompanhem o tema.
*Alternativa:* classe no `<body>` via Vue apenas — descartada por piscar o tema claro no carregamento.

### D3. Componentes base em `src/components/ui/`
`AppButton` (variantes primary/secondary/ghost/danger, tamanho, estado loading com spinner, slot de ícone), `AppCard`, `AppBadge` (tons neutral/success/danger/info/primary), `AppIcon` (conjunto pequeno de ícones SVG inline tipo Lucide copiados como paths, `aria-hidden` por padrão), `PageHeader` (título, subtítulo, slot de ações), `EmptyState`, `SkeletonBlock`, `ErrorState` (mensagem + "Tentar novamente"), `AppPagination`, `ProgressBar`, `ScoreRing` (SVG circular do percentual).
Mantemos as classes globais `.btn`/`.card` como base de estilo dos componentes para uma migração gradual e sem regressões.
*Alternativa:* só classes CSS globais — descartada porque confirmação/toast/skeleton precisam de comportamento e a repetição de markup nas views é justamente parte do problema.

### D4. Confirmação e toasts como serviços globais (composables + host único)
- `useToast()` expõe `success(msg)`, `error(msg)`, `info(msg)`; estado em um módulo reativo singleton (sem Pinia, pois é estado de UI efêmero). `ToastHost` montado uma vez em `App.vue`, com `role="status"`/`aria-live="polite"` (erros com `role="alert"`); sucesso some em ~4 s, erro fica até fechar.
- `useConfirm()` retorna `confirm({ title, message, confirmLabel, tone }) → Promise<boolean>`; `ConfirmDialog` montado uma vez em `App.vue`, implementado com o elemento nativo `<dialog>` + `showModal()` (foco preso e Esc nativos), guardando `document.activeElement` para devolver o foco ao fechar.
Isso permite trocar `if (!confirm(...))` por `if (!(await confirm(...)))` sem reestruturar as views.
*Alternativa:* modal por view com `v-if` — descartada por duplicação.
- Toasts que sobrevivem à navegação: como o estado é singleton fora do componente, o toast "Prova salva" disparado antes do `router.push` continua visível na lista.

### D5. Layout e navegação
`App.vue` ganha um shell: topbar com marca (ícone SVG de chama + "Fênix Provas"), navegação com indicador de rota ativa, `ThemeToggle`, avatar com iniciais + nome + papel e botão "Sair" com ícone. Em telas estreitas a navegação vai para uma segunda linha rolável. Conteúdo com largura máxima e ritmo vertical consistente via `PageHeader`.
A tela inicial vira dois cartões de perfil grandes com ícone, descrição, seletor e botão, sobre um fundo com leve gradiente dos tokens.

### D6. Experiência do aluno
- `ExamTakeView`: uma única barra fixa no rodapé (`position: sticky; bottom`) reúne a `ProgressBar` "X de N respondidas", "faltam N questões" e "Enviar respostas" → `useConfirm`. (Ajuste feito na implementação: prender a barra abaixo da topbar dependeria da altura variável da topbar no mobile.) Alternativas como `<label>` com `input type="radio"` visualmente oculto (mantém teclado/setas nativos) + marcador de letra (A, B, C…) derivado do índice; estado selecionado com borda, fundo `--color-primary-soft`, letra preenchida e ícone de check.
- `ResultView`: `ScoreRing` com percentual, acertos/total e mensagem curta por faixa (ex.: ≥ 70% "Mandou bem!"); detalhamento com `AppBadge` "Correta"/"Incorreta" + ícone.
- `ExamListView` (aluno): cartões com contagem de questões, badge "Concluída · N%" e CTA.

### D7. Telas do professor
- Lista de provas: cartões com metadados em badges, ações Editar/Excluir como botões com ícone e `title`/texto de motivo quando bloqueadas (mantém o requisito de `exam-management`), exclusão via `useConfirm` + `useToast` com a mensagem da API em 403/409.
- Formulário: cartões numerados por questão, alternativas com seletor "correta" destacado (radio estilizado + badge "Correta"), botões de remover só com ícone e `aria-label`, barra de ações fixa no rodapé, toast de sucesso ao salvar.
- Dashboard: mesmos dados e seções; cartões de métrica com ícone e tons, tabelas com cabeçalho discreto, linhas zebradas suaves, desvio positivo/negativo com seta + cor, posição 1–3 do ranking com medalha/badge; paginação via `AppPagination`. Sem gráficos (fora de escopo).

### D8. Fonte e idioma
`index.html` com `lang="pt-BR"`, `<meta name="theme-color">` e fonte Inter via Google Fonts (`display=swap`) com fallback `system-ui`. Se o carregamento externo falhar (ex.: ambiente offline), a UI continua funcional com a fonte do sistema.

## Risks / Trade-offs

- [Regressão visual/funcional ao mexer em todas as views] → alterar só template e estilos, preservar `script setup` (exceto troca de confirm/alert e chamadas de toast); checklist manual de fluxos nas tasks e `npm run build` ao final.
- [Contraste insuficiente no tema escuro] → definir pares texto/fundo dos tokens já verificando 4,5:1 e revisar os tons "soft" dos badges nos dois temas.
- [`<dialog>` com comportamentos diferentes entre navegadores] → suporte é amplo nos navegadores atuais; tratar `cancel` (Esc) e clique no backdrop explicitamente.
- [Dependência de fonte externa] → fallback de sistema; não bloqueia renderização.
- [Crescimento do bundle] → componentes pequenos e ícones SVG inline só dos usados; sem bibliotecas novas.

## Migration Plan

Mudança apenas de frontend, sem migração de dados. Deploy pelo fluxo atual (`docker compose up --build frontend`). Rollback = reverter o commit.
