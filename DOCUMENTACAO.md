# SubmarineMultiCore — Documentação do Projeto

## Objetivo

Servidor Minecraft Bedrock **(Submarine 2.4.0.0)** rodando em Docker numa VPS Alpine, com suporte ao protocolo **944** (Minecraft 1.26.10+).

Repositório: `https://github.com/xandoofc/leni-pocketmine`

---

## Acesso à VPS

| Info | Valor |
|---|---|
| IP | `187.127.17.53` |
| Usuário | `root` |
| Autenticação | Chave SSH (`/tmp/vps_key`) |
| SO | Alpine Linux |

```bash
ssh -i /tmp/vps_key root@187.127.17.53
```

## Comandos Docker

```bash
# Construir imagem
docker compose build --no-cache

# Iniciar servidor
docker compose up -d

# Ver logs
docker compose logs -f
docker logs leni-pocketmine --tail 50

# Anexar ao console do servidor
docker attach leni-pocketmine
# (Ctrl+P + Ctrl+Q para desanexar)

# Parar
docker compose down

# Reiniciar
docker compose down && docker compose up -d

# Executar comando no container
docker exec leni-pocketmine /app/bin/php7/bin/php -v
```

---