FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    ca-certificates \
    libcurl4 \
    libssl3 \
    libpng16-16 \
    libzip4 \
    libsqlite3-0 \
    libyaml-0-2 \
    libxml2 \
    libdeflate0 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

RUN chmod +x bin/php7/bin/php

ENV LD_LIBRARY_PATH=/app/bin/php7/lib

EXPOSE 19132/udp

CMD ["/app/bin/php7/bin/php", "/app/src/pocketmine/PocketMine.php"]
