FROM php:8.3-zts-alpine AS builder

RUN apk add --no-cache $PHPIZE_DEPS \
    linux-headers \
    gmp-dev \
    yaml-dev \
    snappy-dev \
    leveldb-dev \
    git

RUN docker-php-ext-install bcmath sockets gmp

RUN pecl install igbinary yaml && docker-php-ext-enable igbinary yaml

RUN for repo in \
    "https://github.com/pmmp/pmmpthread" \
    "https://github.com/pmmp/chunkutils2" \
    "https://github.com/pmmp/ext-morton" \
    "https://github.com/pmmp/php-leveldb" \
    "https://github.com/pmmp/php-crypto" \
    "https://github.com/pmmp/ext-libdeflate"; do \
    git clone --depth 1 "$repo" /tmp/ext && \
    cd /tmp/ext && \
    phpize && ./configure && make -j$(nproc) && make install && \
    cd / && rm -rf /tmp/ext; \
    done

FROM php:8.3-zts-alpine

RUN apk add --no-cache \
    bash \
    curl \
    ca-certificates \
    gmp \
    yaml \
    leveldb \
    snappy \
    libstdc++

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

RUN docker-php-ext-enable \
    bcmath sockets gmp igbinary yaml opcache \
    pmmpthread chunkutils2 morton leveldb crypto libdeflate

WORKDIR /app

COPY . .

RUN rm -rf bin/php7

EXPOSE 19132/udp

CMD ["php", "src/pocketmine/PocketMine.php"]
