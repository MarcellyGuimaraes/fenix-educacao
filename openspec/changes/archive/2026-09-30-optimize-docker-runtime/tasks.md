# Tasks

## 1. Linha de base

- [x] 1.1 Registrar a medição "antes" (10 chamadas aquecidas de `GET /api/teachers`, `/api/student/exams` e `/api/dashboard/summary`, com `curl -w %{time_total}`) num script no scratchpad reutilizável na tarefa 6.2; verificar que o script imprime o mínimo, a mediana e o máximo por endpoint

## 2. Imagem do backend (design D1, D2, D3, D6)

- [x] 2.1 Criar `backend/.dockerignore` (`vendor/`, `node_modules/`, `.env`, `storage/logs/*`, `storage/framework/{cache,sessions,views}/*` sem apagar os `.gitignore`, `bootstrap/cache/*.php`, `.git`, `public/hot`); verificar com `docker build` que o contexto enviado fica pequeno (sem `vendor/`)
- [x] 2.2 Criar `backend/docker/php/app.ini` com OPcache e PCOV controlados por `${PHP_OPCACHE_VALIDATE_TIMESTAMPS}`/`${PHP_PCOV_ENABLED}` e `realpath_cache_ttl`; verificar dentro do container que `php-fpm -i` mostra `opcache.validate_timestamps => Off` e `pcov.enabled => Off`
- [x] 2.3 Reescrever `backend/Dockerfile`: `ENV` padrão, cópia de `composer.json`/`composer.lock` → `composer install --no-scripts` → `COPY . .` → `composer dump-autoload --optimize` (com os scripts do Laravel), permissões de `storage/` e `bootstrap/cache/`, e cópia do `app.ini`; verificar com `docker compose build app` sem erros
- [x] 2.4 Ajustar `backend/docker/php/entrypoint.sh`: `composer install` só se não existir `vendor/autoload.php`; `optimize` ou `optimize:clear` conforme `APP_OPTIMIZE`, depois de `.env`/`APP_KEY` e das migrations; verificar pelo log de boot que `optimize` rodou e que `bootstrap/cache/config.php` existe no container
- [x] 2.5 Adicionar `APP_CONFIG_CACHE`, `APP_ROUTES_CACHE` e `APP_EVENTS_CACHE` apontando para arquivos inexistentes em `backend/phpunit.xml` (design D4); verificar que `docker compose exec app php artisan test` passa com a config em cache e que os dados do Postgres não mudam (contagem de `exams` antes e depois)

## 3. nginx da API (design D5)

- [x] 3.1 Criar `backend/docker/nginx/Dockerfile` copiando `public/` e o `default.conf`; verificar que `GET /favicon.ico` e `GET /robots.txt` vêm do nginx (200) e que `GET /api/teachers` e `/api/documentation` funcionam

## 4. Frontend (design D7)

- [x] 4.1 Criar `frontend/docker/nginx.conf` com fallback de SPA (`try_files $uri /index.html`) e cache longo para `/assets/`; verificar com a tarefa 4.2
- [x] 4.2 Reescrever `frontend/Dockerfile` em multi-stage (`deps` → `dev` e `build` → `prod`), com `ARG VITE_API_URL`; verificar que `docker compose build frontend` gera a imagem e que `GET /professor/dashboard` em `:5173` devolve o `index.html` (200)

## 5. Compose (design D6, D8)

- [x] 5.1 Atualizar `docker-compose.yml`: remover os bind mounts de `app`, `nginx` e `frontend`; `nginx` passa a usar build próprio; `frontend` com `target: prod`, `build.args.VITE_API_URL` e `5173:80`; `app` com `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning` e `APP_OPTIMIZE=true`; verificar com `docker compose config` sem erros
- [x] 5.2 Criar `docker-compose.dev.yml` (bind mounts, volume `backend_vendor`, `APP_DEBUG=true`, `APP_OPTIMIZE=false`, `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`, frontend `target: dev`); verificar subindo com os dois arquivos, alterando uma string em `backend/app` e outra em `frontend/src`, e vendo as duas alterações refletidas sem `--build` (depois, reverter as alterações)
- [x] 5.3 Medir o modo dev com o script da 1.1 e anotar os números; verificar que o script roda e os valores ficam registrados para a documentação (sem meta obrigatória)

## 6. Documentação

- [x] 6.1 Atualizar o `README.md`: seção "Como rodar" (modo padrão x modo dev), comando de cobertura com `-e PHP_PCOV_ENABLED=1`, `VITE_API_URL` como build arg, nota de performance com os números antes/depois, e remover "OPcache e vendor/ em volume dedicado" dos próximos passos; verificar que cada comando citado roda como está escrito
- [x] 6.2 Atualizar `docs/ARCHITECTURE.md`: diagrama de topologia (frontend nginx estático, nginx com imagem própria, sem bind mounts), passos de boot do app (`optimize`) e a menção ao modo dev; verificar relendo o documento contra o compose final

## 7. Verificação de integração

- [x] 7.1 A partir do estado limpo (`docker compose down`, remover as imagens do projeto e `docker compose up --build`), verificar os cenários de "Execução em um único comando": frontend, API e Swagger respondem e há perfis do seeder; depois `down` + `up` sem duplicar dados (contagem de `teachers`, `students` e `exams` igual)
- [x] 7.2 Rodar o script da 1.1 no modo padrão; verificar que todas as chamadas aquecidas ficam abaixo de 500 ms e registrar antes/depois
- [x] 7.3 Rodar o roteiro do fluxo completo (`flow.sh`, criado na change anterior) contra o modo padrão; verificar os 24 checks OK
- [x] 7.4 Verificar o erro 500 sem detalhes: provocar uma exceção não tratada de forma temporária e reversível (por exemplo, derrubar o Redis com `docker compose stop redis` e chamar `/api/dashboard/summary`), confirmar que o corpo não tem `trace`/`file`/`line` e subir o Redis de novo; confirmar também que o 422 de `POST /exams` inválido mantém `errors`
- [x] 7.5 Rodar `docker compose exec -e PHP_PCOV_ENABLED=1 app php artisan test --coverage`; verificar a suíte verde e a cobertura exibida (~94%)
- [x] 7.6 Teste manual rápido na SPA (`:5173`) pelos dois perfis, conferindo a navegação e o recarregamento (F5) numa rota interna; verificar a ausência de erros no console
