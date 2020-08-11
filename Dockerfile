FROM php:apache
LABEL maintainer="Luke Tainton <luke@tainton.uk>"
COPY --chown=www-data:www-data hesk /srv
COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
RUN docker-php-ext-install mysqli imap
RUN a2enmod rewrite
