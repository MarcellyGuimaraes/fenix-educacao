# Proposal

## Why

No ambiente Docker atual, cada requisição à API leva entre **7 e 22 segundos** (medido em 2026-09-29: `GET /api/teachers` ~18–22 s, `GET /api/student/exams` ~7–8 s, `GET /api/dashboard/summary` ~8–9 s, e até o `/up` ~14 s). A causa é de infraestrutura, não de código. O compose monta `./backend` inteiro (inclusive `vendor/`) dentro do container; no Docker Desktop do Windows essa montagem é `9p/drvfs`, e cada `stat`/leitura de arquivo cruza a fronteira Windows ↔ VM. Um request do Laravel toca centenas de arquivos, e só o `require vendor/autoload.php` leva ~1,4 s. O enunciado do desafio avisa que a performance será avaliada, e o avaliador vai rodar exatamente `docker compose up --build`.

## What Changes

- O `docker compose up --build` padrão passa a rodar uma **imagem otimizada**:
  - o código do backend é copiado para a imagem e o `composer install` roda no build (acabam o bind mount e o `vendor/` sobre 9p);
  - OPcache sem revalidação de timestamps, com cache de config, rotas e eventos (`php artisan optimize`) no boot;
  - `APP_DEBUG=false` e log em nível `warning`;
  - PCOV instalado, mas **desligado** por padrão, e ligado só para gerar cobertura.
- O nginx passa a ter imagem própria, com o `public/` do backend copiado, em vez de montar `./backend`.
- O frontend padrão passa a ser um **build estático** (`vite build`), servido por nginx na mesma porta 5173, com fallback de SPA.
- Novo `docker-compose.dev.yml`, opcional, para desenvolvimento: bind mount do código com `vendor/` em volume nomeado, OPcache revalidando, `APP_DEBUG=true` e Vite dev server com HMR.
- A suíte de testes continua rodando no container padrão mesmo com a configuração em cache.
- README atualizado: modos padrão e dev, comando de cobertura e a nota sobre performance. `docs/ARCHITECTURE.md` atualizado: topologia e boot.

## Capabilities

### New Capabilities
- `docker-runtime`: como a aplicação sobe e responde no ambiente Docker. Cobre a execução em um comando, o tempo de resposta da API, o modo de desenvolvimento e a execução dos testes dentro do container.

### Modified Capabilities
<!-- Nenhuma: exam-management e exam-attempts não mudam de comportamento. -->

## Impact

- **Arquivos**: `docker-compose.yml`, novo `docker-compose.dev.yml`, `backend/Dockerfile`, novo `backend/.dockerignore`, `backend/docker/php/entrypoint.sh`, novo `backend/docker/php/*.ini`, novo `backend/docker/nginx/Dockerfile`, `frontend/Dockerfile`, novo `frontend/docker/nginx.conf`, `backend/phpunit.xml`, `README.md`, `docs/ARCHITECTURE.md`.
- **Fluxo de desenvolvimento**: no modo padrão, mudar código exige `docker compose up --build`. Para editar com reload, use `docker compose -f docker-compose.yml -f docker-compose.dev.yml up`.
- **Build**: `VITE_API_URL` passa a ser argumento de build do frontend (antes era variável de runtime do Vite dev).
- **Erros da API**: com `APP_DEBUG=false`, erros 500 deixam de expor stack trace. Os formatos 4xx não mudam.
- Sem mudanças em código de domínio, migrations ou contratos da API.
