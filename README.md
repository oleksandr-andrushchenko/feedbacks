# https://bref.sh/docs/

# Run ngrok tunnel

[//]: # (`ngrok http --host-header=rewrite https://feedbacks.localhost`)
`ngrok http --host-header=rewrite http://localhost:8000`

# Refresh db
* php bin/console doctrine:database:drop --force
* php bin/console doctrine:database:create
* rm -rf migrations/*
* php bin/console doctrine:migrations:diff
* php bin/console doctrine:migrations:migrate

# Create test db
* docker compose exec -it mysql sh
* mysql -uroot -p1111
* CREATE DATABASE IF NOT EXISTS app_test;
* GRANT ALL PRIVILEGES ON app_test.* TO 'app'@'%';

# TODO
* queues
* soft deletes
* subscribe on specific feedbacks
* generate badge (picture) with username/rating to post in messengers