#change docker/README.md if you change this line
FROM ubuntu:20.04
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

# add node specific linux packages
RUN apt-get clean && apt-get update && apt-get install -yq \
    build-essential

# install servers: node
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get clean && apt-get update && apt-get install -yq nodejs

# install global node packages
RUN npm install --quiet -g jshint

# set work directory
WORKDIR /var/www/html

# add linux packages needed for npm install
RUN apt-get update && apt-get install -yq \
   autoconf \
   libpng-dev

# install all node packages
ADD ./package.json /var/www/html/package.json
RUN npm cache clean --force
RUN npm install --quiet

# add start file to new environment
COPY ./docker/node/start.sh /var/www/docker/node/start.sh
RUN sed -i 's/\r//' /var/www/docker/node/start.sh # Modify the executable to have the linux line endings
RUN chmod u+x /var/www/docker/node/start.sh # Give permissions to run executable

# clean up machine
RUN npm cache clean --force
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
