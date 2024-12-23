arg=$(filter-out $@,$(MAKECMDGOALS))
start:
	docker compose up $(arg)
stop:
	docker compose stop
restart:
	make stop
	make start
migrate:
	php7.4 bin/console doctrine:migrations:migrate $(arg)
install:
	php7.4 /usr/local/bin/composer install
update:
	php7.4 /usr/local/bin/composer update
dump-autoload:
	php7.4 /usr/local/bin/composer dump-autoload
clear-metadata:
	php7.4 bin/console doctrine:cache:clear-metadata
	php7.4 bin/console doctrine:migrations:sync-metadata-storage