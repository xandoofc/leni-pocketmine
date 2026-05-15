FROM php:8.3-zts-alpine AS builder

RUN apk add --no-cache $PHPIZE_DEPS \
    linux-headers \
    gmp-dev \
    yaml-dev \
    snappy-dev \
    git \
    autoconf \
    g++ \
    make \
    cmake \
    curl-dev \
    openssl-dev

RUN docker-php-ext-install bcmath sockets gmp

RUN pecl install igbinary yaml && docker-php-ext-enable igbinary yaml

# Build pmmpthread
RUN git clone --depth 1 https://github.com/pmmp/pmmpthread /tmp/pmmpthread \
    && cd /tmp/pmmpthread \
    && phpize && ./configure --enable-pmmpthread \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/pmmpthread

# Build chunkutils2
RUN git clone --depth 1 https://github.com/pmmp/chunkutils2 /tmp/chunkutils2 \
    && cd /tmp/chunkutils2 \
    && phpize && ./configure \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/chunkutils2

# Build morton
RUN git clone --depth 1 https://github.com/pmmp/ext-morton /tmp/morton \
    && cd /tmp/morton \
    && phpize && ./configure \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/morton

# Build LevelDB from source with zlib/zstd support (needed by php-leveldb)
RUN apk add --no-cache zstd-dev \
    && git clone --depth 1 https://github.com/pmmp/LevelDB /tmp/leveldb \
    && cd /tmp/leveldb \
    && cmake -DCMAKE_BUILD_TYPE=Release \
        -DBUILD_SHARED_LIBS=ON \
        -DLEVELDB_ZLIB_COMPRESSION=ON \
        -DLEVELDB_ZSTD_COMPRESSION=ON \
        -DLEVELDB_SNAPPY_COMPRESSION=ON \
        . \
    && make -j$(nproc) \
    && cp -P libleveldb.so* /usr/local/lib/ \
    && cp -r include/leveldb /usr/local/include/ \
    && rm -rf /tmp/leveldb

# Build php-leveldb
RUN git clone --depth 1 https://github.com/pmmp/php-leveldb /tmp/php-leveldb \
    && cd /tmp/php-leveldb \
    && phpize && ./configure \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/php-leveldb

# Build php-crypto
RUN git clone --depth 1 https://github.com/pmmp/php-crypto /tmp/crypto \
    && cd /tmp/crypto \
    && phpize && ./configure \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/crypto

# Build ext-libdeflate
RUN git clone --depth 1 https://github.com/pmmp/ext-libdeflate /tmp/libdeflate \
    && cd /tmp/libdeflate \
    && phpize && ./configure \
    && make -j$(nproc) && make install \
    && rm -rf /tmp/libdeflate

FROM php:8.3-zts-alpine

RUN apk add --no-cache \
    bash \
    curl \
    ca-certificates \
    gmp \
    yaml \
    snappy \
    libstdc++

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/lib/libleveldb.so* /usr/local/lib/

RUN docker-php-ext-enable \
    bcmath sockets gmp igbinary yaml opcache \
    pmmpthread chunkutils2 morton leveldb crypto libdeflate

ENV LD_LIBRARY_PATH=/usr/local/lib

WORKDIR /app

COPY . .

RUN rm -rf bin/php7

EXPOSE 19132/udp

CMD ["php", "src/pocketmine/PocketMine.php"]
