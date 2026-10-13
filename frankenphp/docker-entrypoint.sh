#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ]; then
	if [ ! -f vendor/autoload_runtime.php ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# cache:clear would boot the previous container, which breaks when the code changed under it.
	rm -rf var/cache/*
	php bin/console cache:warmup -q

	until php bin/console dbal:run-sql -q "SELECT 1" >/dev/null 2>&1; do
		echo 'Waiting for the database...'
		sleep 1
	done

	if [ "$1" = 'frankenphp' ]; then
		php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration -q
		php bin/console app:seed
	fi
fi

exec docker-php-entrypoint "$@"
