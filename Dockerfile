FROM dunglas/frankenphp:1.13.1-php8.5

WORKDIR /app

RUN install-php-extensions @composer apcu intl opcache pdo_pgsql spx \
	&& mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV COMPOSER_ALLOW_SUPERUSER=1 \
	APP_ENV=prod \
	APP_DEBUG=0

COPY frankenphp/conf.d/ $PHP_INI_DIR/conf.d/
COPY frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
