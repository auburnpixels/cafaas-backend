.DEFAULT_GOAL := help

notify = osascript -e 'display notification $(1) with title "DevApprentice"'

setup:
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose build --no-cache
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose up -d  --remove-orphans
	docker-compose exec -e COMPOSER_MEMORY_LIMIT=-1 -u nobody app composer install
	docker-compose exec -u nobody app npm install
	docker-compose exec -u nobody app npm run dev
	docker-compose exec -u nobody app php artisan migrate:fresh --seed
	docker-compose exec -u nobody app php artisan storage:link
	docker-compose exec -u nobody app php artisan config:cache
	docker-compose exec -u nobody app php artisan key:generate
	$(call notify, "Environment Ready")

## Creates and starts the docker containers.
up:
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose up --build -d
	docker-compose ps

## Creates and starts the docker containers for productions
up-prod:
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d
	docker-compose ps

## Stops and removes the docker containers.
down:
	docker-compose down

## Destroys the environment (including volumes mounted).
destroy:
	docker-compose down -v

## Refresh database.
refresh-database:
	docker-compose exec -u nobody app php artisan migrate:fresh --seed

## Get container logs `make logs CMD="-f"`.
logs:
	docker-compose logs $(CMD)

## Enter a running container.
enter:
	docker-compose exec -u nobody app bash

## Refresh PHP resources.
php:
	docker-compose exec -e COMPOSER_MEMORY_LIMIT=-1 -u nobody app composer install
	docker-compose exec -u nobody app php artisan migrate

## Refresh JS resources.
js:
	docker-compose exec -u nobody app npm install
	docker-compose exec -u nobody app npm run dev

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'
