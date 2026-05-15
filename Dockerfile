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
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

EXPOSE 19132/udp
EXPOSE 19132/tcp

VOLUME ["/app/worlds", "/app/plugins", "/app/players", "/app/plugin_data", "/app/crashdumps"]

ENV DO_LOOP=yes

CMD ["bash", "start.sh", "-l"]
