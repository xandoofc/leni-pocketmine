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

---

## Estrutura de Diretórios

```
~/leni-pocketmine/
├── Dockerfile              # Imagem Docker (base: debian:bookworm-slim)
├── docker-compose.yml      # Orquestração do container
├── .dockerignore           # Arquivos ignorados no build
├── .gitignore
├── start.sh                # Script de inicialização original
├── start.cmd
│
├── bin/php7/               # PHP 8.3 pré-compilado com extensões customizadas
│   └── bin/php             # Binário PHP (glibc)
│
├── src/                    # Código fonte do servidor (Submarine fork)
│   └── pocketmine/
│       ├── Player.php      # Lógica do jogador (login, packets)
│       ├── Server.php      # Lógica principal do servidor
│       ├── network/
│       │   └── mcpe/
│       │       ├── protocol/        # Definições de pacotes de rede
│       │       │   ├── LoginPacket.php       # ✅ Modificado (suporte PlayFab)
│       │       │   ├── StartGamePacket.php   # ✅ Modificado (encodePayload944)
│       │       │   ├── LevelChunkPacket.php  # ✅ Modificado
│       │       │   └── ResourcePackStackPacket.php # ✅ Modificado
│       │       ├── raklib/RakLibInterface.php
│       │       └── PlayerNetworkSessionAdapter.php # ✅ Modificado
│       ├── resources/vanilla/
│       │   ├── items/944/    # ✅ Adicionado (item_id_map, required_item_list)
│       │   └── block/944/    # ✅ Adicionado (required_block_states)
│       └── raklib/           # Biblioteca de rede RakNet
│
├── pocketmine.yml          # Config do servidor
├── server.properties       # Propriedades do servidor
└── submarine.yml           # Config do Submarine
```

---

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

## Progresso das Modificações

### Login (Protocolo 944 ✅)

| Etapa | Status | Arquivo |
|---|---|---|
| PlayFab auth (Token JWT) | ✅ | `LoginPacket.php` |
| Skip JWT chain verification | ✅ | `LoginPacket.php` |
| Username/extraData do Token | ✅ | `LoginPacket.php` |
| UUID offline (md5 do username) | ✅ | `LoginPacket.php` |
| XUID default "0" | ✅ | `LoginPacket.php` |
| identityPublicKey dummy | ✅ | `LoginPacket.php` |
| Encryption desligada (>= 900) | ✅ | `LoginPacket.php` |

### Resource Packs ✅

| Etapa | Status | Arquivo |
|---|---|---|
| Skip behaviorPackStack (>= 860) | ✅ | `ResourcePackStackPacket.php` |
| Skip behaviorPackEntries (>= 729) | ✅ | `ResourcePacksInfoPacket.php` |
| Resource packs desligados | ✅ | `server.properties` |

### Dados de Blocos/Itens ✅

| Etapa | Status | Arquivo |
|---|---|---|
| item_id_map.json (protocolo 944) | ✅ | `resources/vanilla/items/944/` |
| required_item_list.json | ✅ | `resources/vanilla/items/944/` |
| required_block_states.nbt | ✅ | `resources/vanilla/block/944/` |

### StartGamePacket ✅

| Etapa | Status | Arquivo |
|---|---|---|
| encodePayload944 (novo formato) | ✅ | `StartGamePacket.php` |
| Níveis de jogo compactados | ✅ | inline no encodePayload944 |
| serverTelemetryData | ✅ | final do pacote |
| networkPermissions | ✅ | `NetworkPermissions` |

### Pós-Login ✅

| Etapa | Status | Arquivo |
|---|---|---|
| CameraPresetsPacket enviado | ✅ | `Player.php` |
| RequestChunkRadiusPacket handler | ✅ | `PlayerNetworkSessionAdapter.php` |
| ChunkRadiusUpdatedPacket enviado | ✅ | `Player.php::setViewDistance` |

### Rede (RakLib) ✅

| Etapa | Status | Arquivo |
|---|---|---|
| TPS: 100 → 200 | ✅ | `Server.php` |
| Retransmissão: 2.0s → 0.3s | ✅ | `SendReliabilityLayer.php` |
| Window size: 512 → 2048 | ✅ | `SendReliabilityLayer.php` |
| Packets/tick: 100 → 500 | ✅ | `Server.php` |
| MTU máximo: 1000 | ✅ | `pocketmine.yml` |

### ❌ Pendente

| Etapa | Status | Observação |
|---|---|---|
| Chunk serialization (formato 944) | ❌ | LevelChunkPacket extraPayload |
| SubChunkPacket (resposta sob demanda) | ❌ | Cliente pede mas servidor não responde |
| CriativeContentPacket / items | ⚠️ | Pode precisar de ajustes |
| CraftingDataPacket (receitas) | ⚠️ | Formato pode ter mudado |

---

## Problemas Conhecidos

1. **Cliente desconecta ~1.2s após login** — O servidor envia `ChunkRadiusUpdatedPacket` mas o formato dos dados dos chunks (LevelChunkPacket.extraPayload) mudou no protocolo 944. O cliente fecha conexão ao receber dados no formato antigo.

2. **SubChunkRequestPacket não implementado** — Se ativar `CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT`, o cliente pede subchunks via `SubChunkRequestPacket` mas o servidor não responde (handler retorna `false`).

3. **Itens Criativos** — A lista de itens criativos pode estar desatualizada pra protocolo 944 (usando item_id_map do 819 como fallback).

---

## Arquivos Modificados (Resumo)

```
src/pocketmine/network/mcpe/protocol/
├── LoginPacket.php                    # PlayFab auth, Token JWT, offline UUID
├── StartGamePacket.php                # encodePayload944
├── ResourcePackStackPacket.php        # skip behaviorStack >= 860
├── LevelChunkPacket.php               # suporte a subchunkCount normal

src/pocketmine/network/mcpe/
├── PlayerNetworkSessionAdapter.php    # handleRequestChunkRadius

src/pocketmine/
├── Player.php                         # CameraPresetsPacket, post-login marker

src/raklib/generic/
├── SendReliabilityLayer.php           # retransmit delay 0.3s, window 2048

src/raklib/server/
├── Server.php                         # TPS 200, packets/tick 500

src/pocketmine/resources/vanilla/
├── items/944/                         # item_id_map, required_item_list
└── block/944/                         # required_block_states

pocketmine.yml                         # MTU 1000, enable-dev-builds
server.properties                      # resource-pack=off
Dockerfile                             # debian:bookworm-slim
docker-compose.yml                     # volumes persistentes
```

---

## Fluxo de Conexão (Protocolo 944)

```
Cliente                          Servidor
  │                                 │
  ├─ RequestNetworkSettings ──────► │  (RakNet Protocol: 567)
  │                                 │
  │◄─── ResponseNetworkSettings ────┤
  │                                 │
  ├─ LoginPacket (944) ────────────► │  ✅ PlayFab auth via Token JWT
  │                                 │
  │◄─── PlayStatus (LOGIN_SUCCESS) ──┤
  │◄─── ResourcePacksInfo ───────────┤
  ├─ ResourcePackClientResponse ────►│
  │◄─── ResourcePackStack ───────────┤  ✅ Skip behaviorPackStack
  ├─ ResourcePackClientResponse ────►│
  │                                 │
  │◄─── StartGamePacket ─────────────┤  ✅ Novo formato 944
  │◄─── ItemRegistryPacket ──────────┤
  │◄─── CameraPresetsPacket ─────────┤  ✅ Adicionado
  │◄─── AvailableActorIdentifiers ───┤
  │◄─── BiomeDefinitions ────────────┤
  │◄─── SetTime ─────────────────────┤
  │◄─── ChunkRadiusUpdated ──────────┤
  │                                 │
  ├─ RequestChunkRadius ───────────► │  ✅ Handler implementado
  ├─ ServerboundLoadingScreen ─────► │
  │                                 │
  │◄─── LevelChunkPacket ────────────┤  ❌ Formato do extraPayload mudou
  │                                 │
  │◄─── [CLIENT DISCONNECTS] ────────┤
```

---

## Para Melhorar a Conexão

O principal gargalo atual é a **serialização de chunks**. O `LevelChunkPacket.extraPayload` usa o formato antigo do PocketMine-MP 3.x. Para protocolo 944, o formato mudou.

Soluções possíveis:

1. **Atualizar `ChunkSerializer.php`** para produzir o novo formato binário de chunks
2. **Implementar `SubChunkPacket`** para responder às requisições de subchunks do cliente
3. **Desabilitar `enableClientSideChunkGeneration`** no StartGamePacket (já está false)

Sem essas correções, o cliente consegue fazer login completo mas não consegue entrar no mundo (desconecta ao receber chunks).
