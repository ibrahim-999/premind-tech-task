FROM node:20-alpine

RUN apk add --no-cache git openssl bash

RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

EXPOSE 5173

CMD ["sh", "-c", "if [ ! -f package.json ]; then echo '[frontend] no package.json yet — sleeping until scaffolded'; tail -f /dev/null; else pnpm install && pnpm dev --host 0.0.0.0; fi"]
