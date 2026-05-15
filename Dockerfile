FROM alpine:3.20

RUN apk add --no-cache \
    bash \
    ca-certificates \
    gcompat \
    patchelf \
    libgcc \
    libstdc++ \
    curl

WORKDIR /app

COPY . .

RUN chmod +x bin/php7/bin/php \
    && patchelf --set-interpreter /usr/lib/libgcompat.so.0 bin/php7/bin/php \
    && patchelf --set-rpath '$ORIGIN/../lib' bin/php7/bin/php

ENV LD_LIBRARY_PATH=/app/bin/php7/lib

EXPOSE 19132/udp

CMD ["/app/bin/php7/bin/php", "/app/src/pocketmine/PocketMine.php"]
