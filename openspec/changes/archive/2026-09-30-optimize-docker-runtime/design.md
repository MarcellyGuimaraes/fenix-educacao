# Design

## Context

A motivação e as medições estão em `proposal.md`. O diagnóstico, feito dentro do container `fenix_app`, foi este:

- `/var/www/html` é uma montagem `9p` (`aname=drvfs;path=C:\`) vinda do disco do Windows. Todo acesso a arquivo cruza a fronteira da VM do WSL2.
- O OPcache já está ativo, mas com `validate_timestamps=On` e `revalidate_freq=2`: a cada 2 s ele faz `stat` de novo em todos os arquivos incluídos, sobre o 9p.
- Sem `config:cache` nem `route:cache`: o Laravel lê e avalia `config/*.php` e `routes/*.php` a cada request, e o `APP_DEBUG=true` com `LOG_LEVEL=debug` escreve em `storage/logs`, também no 9p.
- O PCOV fica ativo em todo request (`docker-php-ext-pcov.ini`), embora só sirva para cobertura.
- O nginx monta `./backend` só para enxergar `public/`.
- O frontend roda o Vite dev server com bind mount. Não é o gargalo (~0,5 s por módulo na primeira compilação), mas serve módulos não empacotados.
- O entrypoint já faz `migrate --seed` (o seeder é idempotente) e `l5-swagger:generate` no boot.
- O Laravel usa o cache de config se `bootstrap/cache/config.php` existir, e o caminho respeita a variável `APP_CONFIG_CACHE`. Por isso os `<env>` do `phpunit.xml` seriam ignorados com a config em cache.

## Goals / Non-Goals

**Goals:**
- Cumprir a spec `docker-runtime`, com o modo padrão otimizado para quem avalia.
- Manter um modo dev confortável e rápido o bastante, mesmo no Windows.

**Non-Goals:**
- Otimizar consultas SQL ou o código de domínio (as medições apontam só para I/O).
- Trocar o php-fpm por Octane/FrankenPHP, ou adicionar proxy reverso unificado ou HTTPS.
- Imagem de produção endurecida (sem dev deps, usuário não-root, multi-arch).

## Decisions

### D1: Código dentro da imagem no modo padrão
`backend/Dockerfile` passa a copiar o código (`COPY . .`) e rodar `composer install --no-interaction --prefer-dist --optimize-autoloader` no build, em camadas: `composer.json` e `composer.lock` primeiro, para aproveitar o cache do Docker. Sai o bind mount de `./backend` do serviço `app`. Um `backend/.dockerignore` exclui `vendor/`, `node_modules/`, `.env`, `storage/logs/*`, `bootstrap/cache/*.php` e `.git`.
- *As dev deps continuam na imagem:* a suíte de testes roda no container (spec "Testes executáveis no container padrão"), e isso exige phpunit e mockery.
- *Alternativa descartada:* manter o bind mount e só mover `vendor/` para um volume. É menor, mas `app/`, `config/` e `routes/` continuariam no 9p. A usuária escolheu a imagem otimizada.

### D2: OPcache e PCOV configurados por variável de ambiente
Novo `backend/docker/php/app.ini` copiado para `conf.d`:
```
opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}
opcache.memory_consumption=192
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
pcov.enabled=${PHP_PCOV_ENABLED}
realpath_cache_ttl=600
```
O Dockerfile define `ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0` e `ENV PHP_PCOV_ENABLED=0`, e o modo dev sobrescreve para `1`/`0`. O PHP expande `${VAR}` em arquivos `.ini`.
- A cobertura passa a ser gerada com `docker compose exec -e PHP_PCOV_ENABLED=1 app php artisan test --coverage`. Esse `-e` vale para o processo CLI; o php-fpm continua com o PCOV desligado.
- *Alternativa descartada:* remover o PCOV da imagem. Perderíamos a cobertura, que o README destaca.

### D3: `php artisan optimize` no boot, controlado por flag
O entrypoint ganha `APP_OPTIMIZE` (padrão `true` no compose padrão, `false` no dev). Com `true`, roda `php artisan optimize` (config, rotas, eventos e views) **depois** de gerar o `.env`/`APP_KEY` e das migrations, porque o cache de config congela as variáveis de ambiente do container. Com `false`, roda `php artisan optimize:clear`. O passo "instalar dependências" passa a acontecer só se `vendor/autoload.php` não existir (caso do volume vazio no modo dev).
- *Por que no boot e não no build:* `config:cache` precisa do `APP_KEY` e das variáveis do compose, que só existem em runtime.

### D4: Testes ignoram o cache de config
Adicionar `<env name="APP_CONFIG_CACHE" value="bootstrap/cache/config.testing.php" force="true"/>` (e o equivalente `APP_ROUTES_CACHE`, `APP_EVENTS_CACHE`) ao `phpunit.xml`. Como esses arquivos nunca são gerados, a suíte sempre carrega a config fresca com os `<env>` de teste (SQLite em memória, cache `array`).
- *Alternativa descartada:* rodar `config:clear` antes dos testes. Isso desotimizaria o container em execução e depende de o avaliador lembrar do passo.

### D5: nginx com imagem própria
Novo `backend/docker/nginx/Dockerfile` (`FROM nginx:1.27-alpine`, `COPY public/ /var/www/html/public/`, `COPY docker/nginx/default.conf ...`), com contexto `./backend`. O `default.conf` atual serve sem mudanças: arquivos estáticos pelo nginx, o resto via FastCGI para `app:9000`. O `SCRIPT_FILENAME` usa `$realpath_root`, que existe nos dois containers com o mesmo caminho.

### D6: APP_DEBUG e logs no modo padrão
O compose padrão define `APP_ENV=production`, `APP_DEBUG=false` e `LOG_LEVEL=warning`. O handler JSON (`shouldRenderJsonWhen`) já devolve só `{"message": "Server Error"}` em 500 com o debug desligado.
- *Atenção:* com `APP_ENV=production`, o `migrate --force` e o `db:seed` precisam de `--force`. O entrypoint já usa `migrate --force --seed`, o que basta.
- As exceptions de negócio (409) continuam sendo registradas no log, como hoje. Está fora do escopo.

### D7: Frontend multi-stage
`frontend/Dockerfile` com três estágios:
1. `deps`: `npm ci`.
2. `dev`: `CMD npm run dev -- --host 0.0.0.0`, usado pelo modo dev.
3. `build` → `prod`: `ARG VITE_API_URL`, `npm run build`, e `FROM nginx:1.27-alpine` com `dist/` e o `frontend/docker/nginx.conf` (`try_files $uri /index.html`, cache longo para `/assets/*` com hash).

O compose padrão usa `target: prod`, com `VITE_API_URL` como `build.args` e porta `5173:80`. O dev usa `target: dev` com os bind mounts atuais.

### D8: `docker-compose.dev.yml`
Um override explícito, e não um `docker-compose.override.yml`, que é carregado automaticamente e anularia o modo padrão:
- `app`: bind mount `./backend:/var/www/html`, volume nomeado `backend_vendor:/var/www/html/vendor`, `APP_ENV=local`, `APP_DEBUG=true`, `APP_OPTIMIZE=false` e `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`.
- `nginx`: bind mount `./backend/public`.
- `frontend`: `target: dev`, bind mounts `./frontend:/app` e `/app/node_modules`, porta `5173:5173` e `VITE_API_URL` como variável de ambiente.

## Risks / Trade-offs

- [No modo padrão, editar código não tem efeito sem `--build`] → Documentar os dois modos no README. É o comportamento esperado de uma imagem de entrega.
- [O `.env` gerado dentro do container se perde a cada rebuild, e com ele o `APP_KEY`] → Não há dados cifrados nem sessões persistentes que dependam dele (a API é stateless). Aceito.
- [O primeiro build fica mais lento (composer e npm dentro da imagem)] → O cache de camadas cobre os builds seguintes. Medir e registrar o tempo no README, se for relevante.
- [`VITE_API_URL` agora é fixado no build] → Mudar a URL exige rebuild do frontend. Documentar.
- [No modo dev, `app/` continua no 9p] → É aceitável para desenvolvimento, e o `vendor/` em volume já remove a maior parte do custo. Medir e registrar no README.
- [Expansão de `${VAR}` no `.ini` falhar se a variável não existir] → Os `ENV` padrão ficam no Dockerfile, então sempre existem.

## Migration Plan

1. `docker compose down` (os volumes de dados são mantidos).
2. `docker compose up --build`.

Não há migração de dados. Para desenvolver, use `docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build`. Rollback: reverter o merge e subir de novo com `--build`.
