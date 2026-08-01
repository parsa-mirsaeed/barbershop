ARG WORDPRESS_IMAGE=wordpress:php8.2-apache
FROM ${WORDPRESS_IMAGE}

USER root

# WordPress itself uses mysqli, while Gateland's Illuminate database layer uses
# PDO. Keep both the web and WP-CLI images on the same PHP extension baseline so
# activating the gateway cannot take down wp-admin, checkout, or maintenance
# commands with "PDOException: could not find driver".
RUN docker-php-ext-install pdo_mysql \
    && php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);'
