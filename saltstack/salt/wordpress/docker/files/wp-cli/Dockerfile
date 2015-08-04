# based on https://github.com/docker-library/wordpress/blob/master/apache/Dockerfile
FROM php:5.6-apache

RUN a2enmod rewrite

# install the PHP extensions we need
RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev && rm -rf /var/lib/apt/lists/* \
        && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
        && docker-php-ext-install gd
RUN docker-php-ext-install mysqli

VOLUME /var/www/html

# We aren't actually using apache, but at least we can be confident that the permissions are correct!
# wp-cli
RUN cd /tmp \
	&& curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
	&& chmod a+x wp-cli.phar \
	&& mv wp-cli.phar /usr/local/bin/wp
# sudo and mysql-client
RUN apt-get update && apt-get install -y sudo mysql-client

WORKDIR /var/www/html

CMD ["wp"]

