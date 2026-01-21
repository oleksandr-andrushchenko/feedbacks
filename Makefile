include .env
export

# Detect docker compose command
ifeq (, $(shell command -v docker-compose 2>/dev/null))
    ifeq (, $(shell command -v docker 2>/dev/null))
        $(error "Docker is not installed")
    endif
    DC := docker compose
else
    DC := docker-compose
endif

BE_FUNCTION_CONTAINER = be-function
MYSQL_CONTAINER = mysql
DYNAMODB_CONTAINER = dynamodb

.PHONY: help
help: ## Show this help
	@echo "Available commands:"
	@awk -F '## ' '/^[a-zA-Z0-9_-]+:.*##/ { \
		split($$1, a, ":"); \
		printf "  \033[36m%-20s\033[0m %s\n", a[1], $$2 \
	}' $(MAKEFILE_LIST) | sort

.PHONY: ngrok-setup
ngrok-setup: ## Setup ngrok
	@echo "Visit: https://dashboard.ngrok.com/get-started/setup/linux"

.PHONY: ngrok-tunnel
ngrok-tunnel: ## Establish ngrok tunnel
	@echo "🔹 Checking for existing ngrok process..."
	@if grep -q '^NGROK_PID=' .env; then \
		PID=$$(grep '^NGROK_PID=' .env | cut -d '=' -f2); \
		if [ -n "$$PID" ] && ps -p $$PID > /dev/null 2>&1; then \
			echo "⚠️ Killing existing ngrok process $$PID..."; \
			kill $$PID || echo "❌ Failed to kill $$PID"; \
		fi; \
		sed -i '/^NGROK_PID=/c\NGROK_PID=' .env; \
	else \
		echo 'NGROK_PID=' >> .env; \
	fi
	@if ! grep -q '^TELEGRAM_WEBHOOK_BASE_URL=' .env; then \
		echo 'TELEGRAM_WEBHOOK_BASE_URL=' >> .env; \
	fi

	@echo "🔹 Starting new ngrok tunnel on port $(BE_FUNCTION_PORT)..."
	@ngrok http --host-header=rewrite http://localhost:$(BE_FUNCTION_PORT) --log=stdout > /dev/null 2>&1 & \
	NGROK_PID=$$!; \
	echo "✅ Ngrok started with PID $$NGROK_PID"; \
	sed -i "/^NGROK_PID=/c\NGROK_PID=$$NGROK_PID" .env; \
	echo "🔹 Waiting for ngrok to initialize..."; \
	until NGROK_URL=$$(curl -s http://127.0.0.1:4040/api/tunnels | grep -Po '\"public_url\":\"\Khttps?://[^\"]*'); do sleep 1; done; \
	sed -i "/^TELEGRAM_WEBHOOK_BASE_URL=/c\TELEGRAM_WEBHOOK_BASE_URL=$$NGROK_URL" .env; \
	echo "✅ Ngrok URL: $$NGROK_URL"; \
	echo ".env updated with TELEGRAM_WEBHOOK_BASE_URL=$$NGROK_URL"
	$(MAKE) restart-be-function

.PHONY: up
up: ## Build and start all Docker containers
	$(DC) up -d

.PHONY: down
down: ## Stop and remove all Docker containers
	$(DC) down

.PHONY: restart-be-function
restart-be-function: ## Restart be-function Docker container
	$(DC) restart $(BE_FUNCTION_CONTAINER)

.PHONY: restart
restart: down up ## Restart all Docker containers and show status
	$(DC) ps -a

.PHONY: rebuild
rebuild: ## Rebuild all Docker containers and show status
	$(DC) down --remove-orphans
	$(DC) up -d --build --force-recreate
	$(DC) ps -a

.PHONY: composer-install
composer-install: ## Run composer install inside be-function container
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) composer install

.PHONY: console
console: ## Run Symfony console
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console $(filter-out $@,$(MAKECMDGOALS))

.PHONY: tests
tests: ## Run PHPUnit tests
	$(DC) exec -T $(BE_FUNCTION_CONTAINER) php bin/phpunit

.PHONY: warmup-cache
warmup-cache: ## Warm up Symfony cache inside be-function container
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:warmup

.PHONY: clear-cache
clear-cache: ## Clear cache inside be-function
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:clear

.PHONY: import-tg-bots
import-tg-bots: ## Import Telegram bots from CSV file
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:import telegram_bots.csv --no-interaction

.PHONY: import-tg-channels
import-tg-channels: ## Import Telegram bots from CSV file
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:channel:import telegram_channels.csv --no-interaction

.PHONY: sync-bot-webhook
sync-bot-webhook: ## Synchronize Telegram bot webhook
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:webhook:sync wild_s_local_bot

.PHONY: be-function-logs
be-function-logs: ## View be-function function logs
	$(DC) logs $(BE_FUNCTION_CONTAINER) -f

.PHONY: login
login: ## Open shell inside be-function container
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) bash

.PHONY: search
search: ## Search for a Telegram user by name
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:search "Андрущенко Олександр" person_name --country=ua

.PHONY: logs
logs: ## Tail Symfony development logs
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) tail -f /tmp/log/dev.log

.PHONY: mysql-logs
mysql-logs: ## View database (MySQL) container logs
	$(DC) logs $(MYSQL_CONTAINER) -f

.PHONY: mysql-login
mysql-login: ## Open MySQL shell inside database container
	$(DC) exec -it $(MYSQL_CONTAINER) mysql -uroot -p1111 -A app

.PHONY: generate-migration
generate-migration: ## Generate a new Doctrine migration file
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:diff

.PHONY: run-migrations
run-migrations: ## Execute pending Doctrine migrations
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing

.PHONY: create-local-dynamodb
create-local-dynamodb: ## Create local DynamoDB table
	@echo "🚀 Creating local DynamoDB table $(DYNAMODB_TABLE)..."
	if AWS_KEY=dummy AWS_SECRET=dummy \
		aws dynamodb describe-table \
		--profile dummy \
		--region us-east-1 \
		--table-name "$(DYNAMODB_TABLE)" \
		--endpoint-url "http://localhost:$(DYNAMODB_PORT)" > /dev/null 2>&1; then \
		echo "⚠️ Table $(DYNAMODB_TABLE) already exists, skipping creation."; \
	else \
		echo "🧩 Extracting DynamoDB schema from CloudFormation..."; \
		$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console dynamodb:schema:extract > /tmp/dynamodb_schema.json; \
		if [ ! -s /tmp/dynamodb_schema.json ]; then echo '❌ Failed to generate valid DynamoDB schema JSON'; exit 1; fi; \
		echo "📄 Generated schema:"; \
		cat /tmp/dynamodb_schema.json; \
		AWS_KEY=dummy AWS_SECRET=dummy \
		aws dynamodb create-table \
			--profile dummy \
			--region us-east-1 \
			--cli-input-json file:///tmp/dynamodb_schema.json \
			--table-name "$(DYNAMODB_TABLE)" \
			--endpoint-url http://localhost:$(DYNAMODB_PORT) \
			--no-cli-pager; \
		rm -f /tmp/dynamodb_schema.json; \
		echo "✅ DynamoDB table $(DYNAMODB_TABLE) initialized in local DynamoDB"; \
	fi

.PHONY: fetch-local-dynamodb
fetch-local-dynamodb: ## Fetch 100 records from local DynamoDB
	@echo "📦 Fetching 100 records from $(DYNAMODB_TABLE)..."
	AWS_KEY=dummy AWS_SECRET=dummy \
	aws dynamodb scan \
		--profile dummy \
		--table-name "$(DYNAMODB_TABLE)" \
		--limit 100 \
		--endpoint-url "http://localhost:$(DYNAMODB_PORT)" \
		--region us-east-1 \
		--no-cli-pager \
		--output json

.PHONY: drop-local-dynamodb
drop-local-dynamodb: ## Drop DynamoDB table in local DynamoDB
	@echo "🗑️ Dropping local DynamoDB table $(DYNAMODB_TABLE)..."
	if AWS_KEY=dummy AWS_SECRET=dummy \
		aws dynamodb describe-table \
		--profile dummy \
		--region us-east-1 \
		--table-name "$(DYNAMODB_TABLE)" \
		--endpoint-url "http://localhost:$(DYNAMODB_PORT)" > /dev/null 2>&1; then \
		AWS_KEY=dummy AWS_SECRET=dummy \
		aws dynamodb delete-table \
			--profile dummy \
			--region us-east-1 \
			--table-name "$(DYNAMODB_TABLE)" \
			--endpoint-url http://localhost:$(DYNAMODB_PORT) \
			--no-cli-pager; \
		echo "✅ Table $(DYNAMODB_TABLE) deleted from local DynamoDB"; \
	else \
		echo "⚠️ Table $(DYNAMODB_TABLE) does not exist, skipping deletion."; \
	fi

.PHONY: recreate-local-dynamodb
recreate-local-dynamodb: drop-local-dynamodb create-local-dynamodb ## Recreate DynamoDB table in local DynamoDB

.PHONY: fix-permissions
fix-permissions: ## Fix permissions
	sudo chown -R 1001:1001 var/ && chmod 0777 -R var/

.PHONY: drop-doctrine
drop-doctrine: ## Drop local Doctrine DB
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:schema:drop --force --full-database

.PHONY: reload-doctrine
reload-doctrine: drop-doctrine run-migrations import-tg-bots import-tg-channels ## Reload local Doctrine DB

.PHONY: reload-dynamodb
reload-dynamodb: recreate-local-dynamodb ## Reload local Dynamodb
	$(DC) exec -it $(BE_FUNCTION_CONTAINER) php bin/console dynamodb:from-doctrine:transfer

.PHONY: reload-bot
reload-bot: ngrok-tunnel sync-bot-webhook # Reload local tg bot

.PHONY: reload-cache
reload-cache: clear-cache fix-permissions # Reload local symfony cache

.PHONY: deploy-params
deploy-params: ## Deploy params
	@echo "🔐 Deploying params in $(AWS_REGION)..."
	aws cloudformation deploy \
		--region "$(AWS_REGION)" \
		--template-file cf-params.yml \
		--stack-name "$(AWS_PROJECT)-$(APP_STAGE)-params" \
		--capabilities CAPABILITY_NAMED_IAM \
		--no-fail-on-empty-changeset \
		--parameter-overrides \
			Project="$(AWS_PROJECT)" \
			Stage="$(APP_STAGE)" \
			AppSecret="$(APP_SECRET)" \
			SiteBaseUrl="$(SITE_BASE_URL)" \
			TelegramWebhookBaseUrl="$(TELEGRAM_WEBHOOK_BASE_URL)" \
			TelegramActivitiesToken="$(TELEGRAM_ACTIVITIES_TOKEN)" \
			TelegramErrorsToken="$(TELEGRAM_ERRORS_TOKEN)" \
			GoogleApiKey="$(GOOGLE_API_KEY)" \
			DbUrl="$(DB_URL)" \
			Crypto="$(CRYPTO)" \
		--tags \
			Project="$(AWS_PROJECT)" \
			Owner="$(AWS_OWNER)" \
			Stage="$(APP_STAGE)" \
			Region="$(AWS_REGION)"
	@echo "✅ Parameters deployment triggered!"

.PHONY: deploy
deploy: ## PROD deploy
	@echo "Using APP_ENV=$(APP_ENV), APP_STAGE=$(APP_STAGE), AWS_PROJECT=$(AWS_PROJECT), AWS_REGION=$(AWS_REGION)"
	@echo "Installing composer dependencies and warming prod cache..."
	$(DC_PROD) run --rm $(BE_FUNCTION_CONTAINER) bash -c " \
		composer install --prefer-dist --optimize-autoloader --no-dev && \
		rm -rf var/cache/$(APP_ENV) && \
		APP_ENV=$(APP_ENV) APP_DEBUG=$(APP_DEBUG) php bin/console cache:clear --env=$(APP_ENV) && \
		APP_ENV=$(APP_ENV) APP_DEBUG=$(APP_DEBUG) php bin/console cache:warmup --env=$(APP_ENV) \
	"
	@echo "Deploying serverless..."
	serverless deploy --region $(AWS_REGION) --stage $(APP_STAGE)
#	@echo "Running migrations..."
#	serverless bref:cli --args="doctrine:migrations:migrate --no-interaction --all-or-nothing" --region $(AWS_REGION) --stage $(APP_STAGE)
	@echo "Installing composer dependencies for local dev..."
	$(DC) exec -T $(BE_FUNCTION_CONTAINER) composer install
	$(DC) exec -T $(BE_FUNCTION_CONTAINER) php bin/console cache:warmup
	@echo "✅ Deployment completed!"

.PHONY: open
open: ## Show local site URL
	@echo "🌐 Visit http://localhost:$(BE_FUNCTION_PORT) in your browser manually."