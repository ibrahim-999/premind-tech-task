.PHONY: help up down build rebuild restart logs ps shell tinker artisan migrate fresh seed test typecheck lint certs clean

DC := docker compose

help:
	@echo "Premind dev commands (run from docker/ directory):"
	@echo ""
	@echo "  make up         start all services in background"
	@echo "  make down       stop all services"
	@echo "  make build      build images"
	@echo "  make rebuild    rebuild images without cache"
	@echo "  make restart    restart all services"
	@echo "  make logs       tail logs from all services"
	@echo "  make ps         list running services"
	@echo ""
	@echo "  make shell      open bash in app container"
	@echo "  make tinker     open Laravel tinker"
	@echo "  make artisan c='cmd'   run arbitrary artisan command"
	@echo "  make migrate    run pending migrations"
	@echo "  make fresh      drop all tables and re-run migrations + seeders"
	@echo "  make seed       run seeders only (idempotent — safe to repeat)"
	@echo ""
	@echo "  make test       run backend tests"
	@echo "  make typecheck  run frontend type check"
	@echo "  make lint       run frontend lint"
	@echo ""
	@echo "  make certs      generate self-signed HTTPS certs"
	@echo "  make clean      stop and remove volumes (DESTRUCTIVE)"

up:
	$(DC) up -d

down:
	$(DC) down

build:
	$(DC) build

rebuild:
	$(DC) build --no-cache

restart:
	$(DC) restart

logs:
	$(DC) logs -f

ps:
	$(DC) ps

shell:
	$(DC) exec app bash

tinker:
	$(DC) exec app php artisan tinker

artisan:
	$(DC) exec app php artisan $(c)

migrate:
	$(DC) exec app php artisan migrate --force

fresh:
	$(DC) exec app php artisan migrate:fresh --seed --force

seed:
	$(DC) exec app php artisan db:seed --force

test:
	$(DC) exec app php artisan test --parallel

typecheck:
	$(DC) exec frontend pnpm typecheck

lint:
	$(DC) exec frontend pnpm lint

certs:
	bash scripts/generate-certs.sh

clean:
	$(DC) down -v
