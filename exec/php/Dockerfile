FROM php:8.2-fpm-buster

ENV PHP_IDE_CONFIG 'serverName=localhost'

ARG PUID=1000
ENV PUID ${PUID}
ARG PGID=1000
ENV PGID ${PGID}

ENV LIBRDKAFKA_VERSION v2.2.0

ENV LIBRDKAFKA_DEPS \
    build-essential \
    libsasl2-dev \
    libssl-dev \
    python-minimal \
    zlib1g-dev

RUN DEBIAN_FRONTEND=noninteractive && apt update && apt dist-upgrade -y && apt install -y --no-install-recommends git libzip-dev zip unzip libicu-dev ${LIBRDKAFKA_DEPS}

# To make use of Kafka in PHP, you need to install the necessary libraries, so...
RUN cd /tmp \
 && git clone \
        --branch ${LIBRDKAFKA_VERSION} \
        --depth 1 \
        https://github.com/confluentinc/librdkafka \
    && cd librdkafka \
    && ./configure \
    && make \
    && make install \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && rm -rf /tmp/librdkafka

RUN apt-get purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
        ${LIBRDKAFKA_DEPS}

RUN DEBIAN_FRONTEND=noninteractive \
    && pecl install xdebug-3.2.2 \
    && pecl install redis-6.0.0 \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-enable xdebug redis intl

RUN docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl;


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -g $PGID user && useradd -u $PUID -g $PGID -s /bin/bash -m user
USER user

ENV PATH /home/user/.composer/vendor/bin:$PATH

COPY ../../ /app
WORKDIR /app
CMD ["php-fpm", "--nodaemonize"]
