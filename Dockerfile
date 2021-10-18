FROM php:7.4-apache

MAINTAINER Marc Apfelbaum karsasmus82@gmail.com

ENV HTTP_DIR /var/www/html
ENV LP_DIR $HTTP_DIR/leafpub

RUN apt-get update && \
    apt-get -y install curl nano && \
    apt-get -y install unzip git gnupg && \
    apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev && \
    apt-get install -y patch  && \
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
    docker-php-ext-install gd pdo_mysql zip iconv

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && apt-get install -y nodejs npm build-essential
RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo deb https://dl.yarnpkg.com/debian/ stable main | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && apt-get install yarn

RUN rm -f $HTTP_DIR/index.html && \
    a2enmod rewrite

ADD docker/apache/server-apache2-vhosts.conf /etc/apache2/sites-enabled/000-default.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN echo "alias ll='ls -ahl --color'" >> /etc/bash.bashrc

WORKDIR $LP_DIR

RUN npm i -g gulp

EXPOSE 80

CMD ["apachectl","-D","FOREGROUND"]
