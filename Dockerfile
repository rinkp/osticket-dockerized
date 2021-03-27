FROM php:7.4-apache

# We need the LDAP extension
RUN apt-get update && \
	apt-get install -y libldap2-dev && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ && \
	docker-php-ext-install ldap && \
	apt-get purge -y --auto-remove libldap2-dev
	
# Configure opcache. In production we may want to override PHP_OPCACHE_VALIDATE_TIMESTAMPS
RUN docker-php-ext-install opcache
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \ 
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="256" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

VOLUME ["/var/www/html"]
EXPOSE 80	