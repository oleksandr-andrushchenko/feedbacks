include .env
export

DC = docker-compose
BE_FUNCTION_CONTAINER = $(DOCKER_NAME)-be-function
RDBMS_CONTAINER = $(DOCKER_NAME)-rdbms

ngrok-tunnel:
	ngrok http --host-header=rewrite http://localhost:$(BE_FUNCTION_PORT)
start:
	docker compose up -d --build --force-recreate
stop:
	docker compose down --remove-orphans
restart: start start
	sleep 1
	docker ps -a
composer-install:
	docker exec -it $(BE_FUNCTION_CONTAINER) composer install
cache-warmup:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:warmup
cache-clear:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console cache:clear
import-bots:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:import telegram_bots.csv
sync-bot-webhook:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:webhook:sync wild_s_local_bot
be-function-logs:
	docker logs $(BE_FUNCTION_CONTAINER) -f
be-function-login:
	docker exec -it $(BE_FUNCTION_CONTAINER) bash
search:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console telegram:bot:search "Андрущенко Олександр" person_name --country=ua
logs:
	docker exec -it $(BE_FUNCTION_CONTAINER) tail -f var/log/dev.log
rdbms-logs:
	docker logs $(RDBMS_CONTAINER) -f
rdbms-login:
	docker exec -it $(RDBMS_CONTAINER) mysql -uapp -p1111 -A app
generate-migration:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:diff
run-migrations:
	docker exec -it $(BE_FUNCTION_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing