FROM alpine:3.20

RUN apk add --no-cache \
    bash \
    ca-certificates \
    gcompat \
    libgcc

RUN mkdir -p /lib64 && ln -sf /usr/lib/libgcompat.so.0 /lib64/ld-linux-x86-64.so.2

WORKDIR /app

COPY . .

RUN chmod +x bin/php7/bin/php

EXPOSE 19132/udp

ENV DO_LOOP=yes

CMD ["bash", "start.sh", "-l"]
