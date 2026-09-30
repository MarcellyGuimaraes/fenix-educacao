# --- deps: dependências do npm (compartilhadas por dev e build)
FROM node:22-alpine AS deps
WORKDIR /app
COPY package*.json ./
RUN npm ci

# --- dev: Vite dev server com HMR (usado pelo docker-compose.dev.yml)
FROM deps AS dev
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# --- build: bundle estático minificado. A URL da API é fixada aqui.
FROM deps AS build
ARG VITE_API_URL=http://localhost:8080/api
ENV VITE_API_URL=${VITE_API_URL}
COPY . .
RUN npm run build

# --- prod: nginx servindo o dist/ (alvo padrão do docker-compose.yml)
FROM nginx:1.27-alpine AS prod
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
