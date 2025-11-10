include .env
export

DC = docker-compose
BE_FUNCTION_CONTAINER = $(DOCKER_NAME)-be-function
RDBMS_CONTAINER = $(DOCKER_NAME)-rdbms

.PHONY: help
help: ## Show this help
	@echo "Available commands:"
	@awk -F '## ' '/^[a-zA-Z0-9_-]+:.*##/ { \
		split($$1, a, ":"); \
		printf "  \033[36m%-20s\033[0m %s\n", a[1], $$2 \
	}' $(MAKEFILE_LIST) | sort

.PHONY: ngrok-tunnel
ngrok-tunnel: ## Start ngrok tunnel for backend function
	ngrok http --host-header=rewrite http://localhost:$(BE_FUNCTION_PORT)

.PHONY: start
start: ## Build and start all Docker containers
	docker compose up -d --build --force-recreate

.PHONY: stop
stop: ## Stop and remove all Docker containers
	docker compose down --remove-orphans

.PHONY: restart
restart: ## Restart all Docker containers and show status
	$(MAKE) stop
	sleep 1
	$(MAKE) start
	docker ps -a

.PHONY: composer-install
composer-install: ## Run composer install inside backend container
	docker exec -it $(BE_FUNCTION_CONTAINER) composer install

.PHONY: cache-warmup
cache-warmup: ## Warm up Symfony cache inside backend container
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:warmup

.PHONY: cache-clear
cache-clear: ## Clear Symfony cache inside backend container
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:clear

.PHONY: import-bots
import-bots: ## Import Telegram bots from CSV file
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:import telegram_bots.csv

.PHONY: sync-bot-webhook
sync-bot-webhook: ## Synchronize Telegram bot webhook
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:webhook:sync wild_s_local_bot

.PHONY: be-function-logs
be-function-logs: ## View backend function logs
	docker logs $(BE_FUNCTION_CONTAINER) -f

.PHONY: be-function-login
be-function-login: ## Open shell inside backend container
	docker exec -it $(BE_FUNCTION_CONTAINER) bash

.PHONY: search
search: ## Search for a Telegram user by name
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:search "Андрущенко Олександр" person_name --country=ua

.PHONY: logs
logs: ## Tail Symfony development logs
	docker exec -it $(BE_FUNCTION_CONTAINER) tail -f var/log/dev.log

.PHONY: rdbms-logs
rdbms-logs: ## View database (MySQL) container logs
	docker logs $(RDBMS_CONTAINER) -f

.PHONY: rdbms-login
rdbms-login: ## Open MySQL shell inside database container
	docker exec -it $(RDBMS_CONTAINER) mysql -uapp -p1111 -A app

.PHONY: generate-migration
generate-migration: ## Generate a new Doctrine migration file
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:diff

.PHONY: run-migrations
run-migrations: ## Execute pending Doctrine migrations
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing