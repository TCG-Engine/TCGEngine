# Overview
The goal will be to have multiple docker compose targets for the different root names of the TCGEngine.
To start, we will only need two differentiated workflows:
1. SWUDeck - the Deckbuilder version of the TCGEngine for Star Wars Unlimited TCG
2. GrandArchiveSim - the Sim version of the TGEngine for Grand Archive TCG

# Requirements
- REQ-1: existing module is updated for "SWUStats" (the friendly name for SWUDeck) to be separate from the new GrandArchiveSim module
- REQ-2: SWUDeck/SWUStats is still accessible through localhost:3100 and it talks to its own database
- REQ-3: GrandArchiveSim is accessible through localhost:3200 and it talks to its own database without clashing with SWUDeck database
- REQ-4: entries in the docker-compose are clear for which rootname they belong to
  - example: `web-server` becomes `swudeck-web-server` and `mysql-server` becomes `swudeck-mysql-server`
- REQ-5: volumes are also differentiated as in REQ-4
- REQ-6: docker compose override yaml is considered and updated as well

# Validate
- VAL-1: The docker compose yaml file is updated with these pieces
- VAL-2: The new-docker-app.md skill file is updated with a way to run this to append new apps
- VAL-3: the pieces are able to be pinged after running `docker compose up -d`