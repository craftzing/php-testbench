workspace-service := workspace

.PHONY: up
up:
	docker compose up --detach
	docker compose exec ${workspace-service} composer install

.PHONY: down
down:
	docker compose down

.PHONY: test
test:
	docker compose exec ${workspace-service} composer cs:check
	docker compose exec ${workspace-service} composer phpstan
	docker compose exec ${workspace-service} composer phpunit

.PHONY: format
format:
	docker compose exec ${workspace-service} composer cs:fix

.PHONY: phpunit
phpunit:
	docker compose exec ${workspace-service} composer phpunit