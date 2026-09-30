# nginx da API: serve os arquivos estáticos de public/ e repassa o resto
# para o php-fpm (app:9000). Contexto de build: ./backend
FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public/ /var/www/html/public/
