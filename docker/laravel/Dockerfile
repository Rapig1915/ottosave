#change docker/README.md if you change the following line
FROM dym-node-image
MAINTAINER Buink Web Development <online@buink.biz>

# set ubuntu front-end for remote work
ENV DEBIAN_FRONTEND noninteractive

# set locale
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    locales
RUN locale-gen en_US.UTF-8 \
    && export LANG=en_US.UTF-8

# add foundational linux packages
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    git-core \
    vim \
    nano \
    curl \
    cron \
    wget \
    sudo \
    zip \
    unzip

# install servers and server specific linux packages
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    apache2 \
    redis-server \
    mysql-client

# add php specific linux packages
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    software-properties-common

# add packages for version specific php
RUN apt-get clean && apt-get update --fix-missing && LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php #this is a workaround noted here https://github.com/oerdnj/deb.sury.org/issues/56

# install linux packages for php8.1
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    php8.1 \
    php8.1-dev \
    php8.1-common \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-mysql \
    php8.1-xml \
    php8.1-bcmath \
    php8.1-zip

# install linux packages for php and apache
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    php8.1-mcrypt \
    libapache2-mod-php8.1

# install linux packages for php and composer
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    php8.1-gd

# install composer initial laravel dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# install and configure Supervisor
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq supervisor

# install linux packages for php and this project
RUN apt-get clean && apt-get update --fix-missing && apt-get install -yq \
    php8.1-bcmath
RUN echo "bcmath.scale = 2" >> /etc/php/8.1/apache2/php.ini #php8.1
RUN echo "bcmath.scale = 2" >> /etc/php/8.1/cli/php.ini #php8.1

# install composer packages
COPY ./composer.json /var/www/html/composer.json
COPY ./composer.lock /var/www/html/composer.lock
RUN composer install --prefer-dist --no-scripts --no-interaction --no-autoloader

# setup apache
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
RUN ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log
RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR
RUN a2enmod rewrite
COPY ./docker/laravel/apache.conf /etc/apache2/sites-available/000-default.conf

# set work directory
WORKDIR /var/www/html

# add a jobs to the crontab file to run laravel scheduler every minute and restart queue every hour
RUN sed -i '$i* * * * * root cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1' /etc/crontab
RUN sed -i '$i0 * * * * root cd /var/www/html && php artisan queue:restart >> /dev/null 2>&1' /etc/crontab

#configure supervisor
COPY ./docker/laravel/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./docker/laravel/queue-listener.conf /etc/supervisor/conf.d/queue-runner.conf

# add start file to new environment
COPY ./docker/laravel/start.sh /var/www/docker/laravel/start.sh
RUN sed -i 's/\r//' /var/www/docker/laravel/start.sh # Modify the executable to have the linux line endings
RUN chmod u+x /var/www/docker/laravel/start.sh # Give permissions to run executable

# cleanup machine
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
