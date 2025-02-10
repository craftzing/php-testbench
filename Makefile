.PHONY: up
up: ## Up the project using Docker Composer
	docker compose up --detach
	docker compose exec php84 composer install

.PHONY: down
down: ## Shutdown the project using Docker Composer
	docker compose down

.PHONY: php83
php83: ## Open an interactive shell into the `php83` (service in docker-compose.yml)
	docker compose up -d
	docker compose exec php83 sh

.PHONY: php84
php84: ## Open an interactive shell into the `php84` (service in docker-compose.yml)
	docker compose up -d
	docker compose exec php84 sh
