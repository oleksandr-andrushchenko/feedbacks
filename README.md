# Production / Ukraine
* https://t.me/Anonimni_Vidhuky_Ukraina_Bot
* If you like it - give me a star, thank you

## Available commands

```
  be-function-login    Open shell inside backend container
  be-function-logs     View backend function logs
  cache-clear          Clear Symfony cache inside backend container
  cache-warmup         Warm up Symfony cache inside backend container
  composer-install     Run composer install inside backend container
  generate-migration   Generate a new Doctrine migration file
  help                 Show this help
  import-bots          Import Telegram bots from CSV file
  logs                 Tail Symfony development logs
  ngrok-tunnel         Start ngrok tunnel for backend function
  rdbms-login          Open MySQL shell inside database container
  rdbms-logs           View database (MySQL) container logs
  restart              Restart all Docker containers and show status
  run-migrations       Execute pending Doctrine migrations
  search               Search for a Telegram user by name
  start                Build and start all Docker containers
  stop                 Stop and remove all Docker containers
  sync-bot-webhook     Synchronize Telegram bot webhook
```

# Serverless
* https://bref.sh/docs/

# Refresh db
* php bin/console doctrine:database:drop --force
* php bin/console doctrine:database:create
* rm -rf migrations/*
* php bin/console doctrine:migrations:diff
* php bin/console doctrine:migrations:migrate
* php bin/console telegram:bot:import telegram_bots.csv --no-interaction
* php bin/console telegram:channel:import telegram_channels.csv --no-interaction

# Create test db
* docker compose exec -it mysql sh
* mysql -uroot -p1111
* CREATE DATABASE IF NOT EXISTS app_test;
* GRANT ALL PRIVILEGES ON app_test.* TO 'app'@'%';

# TODO
* queues
* generate badge (picture) with username/rating to post in messengers
* "list my feedbacks" command
* "list feedbacks on me" command
* manual payments
* ban users
* allow to buy subscription even if already have one

* Simplify country selection
* Remove confirmation steps for premium-users
* -> Check other channels and put emojis & description as much as possible + html

* Everything should be extremely easy, simple and clear to understand!!!
* Fix limits reply (counts miss-matches)
* Keep feedbacks, feedback searches and feedback lookups even if Limtis exceeded (notify user when found something - maybe as kfor payment before)
* Protect against multi-account (if search term is popular - ask for money even if limits not exceeded)
* Inject service locator in telegram channel for commands (coz many dependencies loadings)

* add bot id/country to payments/subscriptions
* add contacts info (table columns) for each bot
* check if bot can add subscribers - if so - use it just after start using the bot
* bug (?): when user already exists and then change bot - there is still country/locale of the user (not bots country/locale), solution is to
* 1) propose to keep/change current settings on start
* 2) move settings to messenger user layer (+ delete from user) and make messenger user unique per [messenger, id, bot]
* Implemented request_user for keyboard button when asking for search term type (as telegram user possible type - will receive ID!!)
* Replace help replies using php not twig

* Create adv

* Sync groups automatically
* Share link on channel's message with feedbacks (give a chance to discuss the feedback)
* Cache db requests
* Media uploads (S3)
* Validation for search terms: min/max length, emojis, tags etc.
* Make validators configurable via settings (options)
* More money: lock feedbacks and ask money for it
* Apply optimization tricks/tips for symfony prod application
* move translation out of the repo
* check doctrine queries, optimize if need
* if phone number added (create command) and rating is good - ask phone number in order to no allow creation on self
* facebook bot
* process old non-stopped conversations
* cache bots as files


* create all possible search terms (for each type) in case of unknown selected????
* add overal term rating to feedback
* improve all types of searching
* fix bug with empty keyboard when error occurs in message handlers
* ask user to leave a phone number or email for future notifications (is anybody gonna search him)
* add results couns field to feeedbacK-search
* implement the same logic for clarity orgs (as for persons was mada)
* implement rpvoder search term variants as keyboard buttons
* improve secrets for phone searching (add more)
* array_unique for feedbacks & feedback searches
* wrap all search records into object with parsed link (and show it for premium users)
* improve secrets modifier, add brackets for secreted values (just user to know what is hidden)
* add ru names support for blackbox search provider
* add PersonName::matchFirstName method (is will check across all equal meaning names), implement instead of PersonName::getFirst() === $compare
* if crowlings became a big problem - use real browsering like selenium server or some api based on it
* halt searching if bot was deleted by user
* add tips during search - in case if provider not supported, e.g. add surname, use ukrainian language etc.
* fill facebook group with feedbacks (take from other sources)
* create and fill instagram group with feedbacks (take from other sources)
* add more info to feedbacks/searches results (bot etc.)
* resolve cases like: Бажана Катерина (investigate)
* groups same results by days (for search requests for example)
* do not duplicate notifications for each search request or feedback (10 times were searching and when somebody else search it - user receives the same message 10 times)
* 
* filter negative comments only for some search providers
* parse content and hide phone numbers for non-premium users for some search providers
* reduce premium price
* use the same list format for subscriptions as for search providers
* send notification about subscription to the user
* mode FeedbackNotification to NoSQL engine
