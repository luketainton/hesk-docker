FROM php:apache
LABEL maintainer="Luke Tainton <luke@tainton.uk>"
COPY hesk /srv
COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite
