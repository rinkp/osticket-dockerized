FROM php:8.4 AS build-plugins

WORKDIR /osTicket-plugins

RUN apt-get update && \
	apt-get install -y libzip-dev zip && \
	docker-php-ext-install zip

COPY ./osTicket-plugins /osTicket-plugins

RUN COMPOSER_ALLOW_SUPERUSER=1 php ./make.php hydrate

RUN php -dphar.readonly=0 make.php build audit && \
    php -dphar.readonly=0 make.php build auth-2fa && \
    php -dphar.readonly=0 make.php build auth-ldap && \
    php -dphar.readonly=0 make.php build auth-oauth2 && \
    php -dphar.readonly=0 make.php build auth-passthru && \
    php -dphar.readonly=0 make.php build auth-password-policy  && \
    php -dphar.readonly=0 make.php build storage-fs  && \
    php -dphar.readonly=0 make.php build storage-s3


# Build osTicket for release
FROM php:8.4 AS build-osticket

RUN apt-get update && \
	apt-get install -y git

# Import osTicket git
COPY ./ /build
WORKDIR /build/osTicket

RUN php manage.php deploy -v -s -g deployment


# Create osTicket container
FROM php:8.4-apache AS run
# We need the LDAP extension; we don't need to keep libldap2-dev
# Clean up apt-get after each layer to keep layers small
RUN apt-get update && \
	apt-get install -y libldap2-dev && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-configure ldap --with-libdir=lib/ && \
	docker-php-ext-install ldap && \
	apt-get purge -y --auto-remove libldap2-dev
	
# Configure opcache. In development we may want to override PHP_OPCACHE_VALIDATE_TIMESTAMPS
RUN docker-php-ext-install opcache
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \ 
    PHP_OPCACHE_MAX_ACCELERATED_FILES="16229" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="256" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# MySQLi
RUN docker-php-ext-install mysqli

# gdlib
RUN apt-get update && \
	apt-get install -y libpng-dev libpng16-16 && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-install gd && \
	apt-get purge -y --auto-remove libpng-dev
	
# IMAP
RUN apt-get update && \
	apt-get install -y libc-client-dev libkrb5-dev libkrb5-3 && \
	rm -rf /var/lib/apt/lists/* && \
	pecl install imap && \
	docker-php-ext-enable imap && \
	apt-get purge -y --auto-remove libc-client-dev libkrb5-dev
	
# intl
RUN apt-get update && \
	apt-get install -y libicu-dev libicu72 && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-configure intl && \
	docker-php-ext-install intl && \
	apt-get purge -y --auto-remove libicu-dev
	
# apcu
RUN pecl install apcu && \
	docker-php-ext-enable apcu
	
# zip
RUN apt-get update && \
	apt-get install -y libzip-dev libzip4 && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-install zip && \
	apt-get purge -y --auto-remove libzip-dev
    
# mod_rewrite
RUN a2enmod rewrite
	
# Installing cron for cronjobs
RUN apt-get update && \
	apt-get install -y cron && \
	rm -rf /var/lib/apt/lists/*
COPY ./osticketcron /etc/cron.d/osticketcron

# Import default osTicket installation
COPY --from=build-osticket /build/osTicket/deployment/ /var/www/html/

# Import plugins
COPY --from=build-plugins /osTicket-plugins/*.phar /var/www/html/include/plugins/
COPY ./config /config
RUN chmod 644 /config/ost-config.php

# Copy modified installation
COPY ./setup/. /var/www/html/setup/

COPY ./php.ini "$PHP_INI_DIR/php.ini"
COPY ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

HEALTHCHECK --timeout=2s --start-period=10s CMD curl --fail http://localhost || exit 1

# Run both apache2-frontend as well as the cron daemon
ENTRYPOINT ["/docker-entrypoint.sh"]

# Make /var/www/html a recommended volume
VOLUME ["/var/www/attachments"]
EXPOSE 80

