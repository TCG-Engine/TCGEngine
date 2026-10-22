---
name: swustats-melee-package
description: Use when the user gives one or more melee.gg tournament links (up to 5) to get onto SWUStats — "package this melee tournament", "import these melee links", "get this tournament on swustats". melee.gg 403s the swustats.net droplet, so tournaments are recorded locally into packages (.melee-packages/melee-<id>.json.gz) that a moderator uploads with the "Import Tournaments" picker on Stats/MeleeTournaments.php. Orchestrates DevTools/melee-package.php.
---

# SWUStats melee tournament packages

melee.gg's load balancer answers 403 to the DigitalOcean droplet that runs swustats.net (every endpoint, no IPv6), so the server cannot import a tournament itself. Instead:

1. **Here (local):** record the import against melee from this machine, which melee does answer, into a package file.
2. **There (swustats.net):** a moderator (`ApprovedModeratorUserNames()`) uploads up to 5 packages on **Stats/MeleeTournaments.php → Import Tournaments**. The server replays the recorded answers and never contacts melee.

## Input

1 to 5 melee.gg tournament links (`https://melee.gg/Tournament/View/<id>`), or bare ids. More than 5: split into runs of 5 and tell the user they will upload in batches of 5.

## Run

The local swudeck container must be up (`otmtcge-swudeck-web-server-1`).

```bash
docker exec otmtcge-swudeck-web-server-1 php /var/www/html/TCGEngine/DevTools/melee-package.php <link1> [<link2> … <link5>]
```

Big events take minutes (one melee request per decklist; a 1,500-player event is about 1,500 requests). Use a long timeout, or `run_in_background` for anything over about 300 players.

Per link it prints either:

- `OK    <id>  <tournament name> — N decks, M matchups, K melee answers, S KB` followed by `.melee-packages/melee-<id>.json.gz`, or
- `FAIL  <link> — <reason>`. If the reason includes `melee answered HTTP 403`, this machine is blocked too. Say so; don't retry in a loop.

The exit code is 1 if any link failed. The other links' packages are still written.

## What the CLI guarantees

- It never touches the local site's melee tables. Each import runs in a scratch database, `swudeck_meleepkg`, which is recreated per tournament.
- An `OK` package has already been **replayed** into a fresh scratch copy and reproduced the same deck and matchup counts, so it is known to be complete before the user uploads it.

## Report to the user

For each link, give the result line. Then give the package path(s) as clickable links ([.melee-packages/melee-<id>.json.gz](.melee-packages/melee-<id>.json.gz)) and the next step: on swustats.net, signed in as a moderator, open Melee Tournaments, choose the files under **Import Tournaments**, and press **Import**. Each file then reports one of:

- **Imported**
- **Already imported**
- **Failed**, with the reason

A warning means the server's counts differ from what the package recorded.

`.melee-packages/` is gitignored. Packages hold only public melee data, but they don't belong in the repo.

## Pieces

| File | Role |
|---|---|
| `Stats/MeleeFetch.php` | The live/record/replay gateway every melee request goes through |
| `Stats/MeleePackage.php` | Package format, decode/validation, replay import |
| `DevTools/melee-package.php` | This skill's CLI |
| `APIs/ImportMeleeTournamentPackage.php` | Mods-only upload endpoint (≤5 files) |
| `DevTools/tdd-regression/test_melee_fetch_gateway.php`, `test_melee_package_import.php` (CLI), `test_melee_package_endpoint.php` | Tests |
