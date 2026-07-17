---
name: swudeck-session-start
description: Use at the START of a SWUDeck (NOT overall TCGEngine) working session to get oriented — recall past gotchas/lessons, confirm the local dev environment + cross-browser verification tooling are ready, and surface in-flight work — before making changes. Invoke when the user signals they're beginning SWUDeck work ("let's work on SWUDeck", "start a SWUDeck session") or runs /swudeck-session-start.
---

# SWUDeck Session Start

A lightweight start-of-session ritual: load the context that keeps us from repeating past
mistakes, confirm the local environment and verification tools are ready, and surface where
things left off — so the first change lands on solid footing. This is the counterpart to
`swudeck-session-close`.

## When to run

- The user signals they're beginning SWUDeck work.
- Explicitly invoked (`/swudeck-session-start`).
- Before a substantive SWUDeck change when you haven't yet oriented this session.

Skip it for a one-line question you can answer immediately.

## Steps

Create a todo per step and work through them in order.

### 1. Load carried-forward context

These are the traps that already bit us — read them before touching code:

- **Project memory** — the `MEMORY.md` index is auto-loaded each session; skim it and read the
  specific notes that bear on today's task (e.g. the Firefox `height:100%`/flex-stretch gotcha
  for a layout change; the SET_NNN-vs-UUID bridge for anything touching format legality; the
  image-generator regen workflow for crop/art changes).
- **Retro log** — `.claude/skills/swudeck-session-close/references/lessons-learned.md`. Skim the
  newest entries for process lessons and any **known follow-ups** left open.

Call out anything that directly changes today's approach.

### 2. Confirm the dev environment is up

SWUDeck runs in Docker, served at `http://localhost:3100/TCGEngine`. Verify:

- Containers running: `docker ps | grep swustats` — expect the web server, mysql, and redis
  (`otmtcge-swustats-web-server-1`, `-mysql-server-1`, `swustats_app_redis`; names may vary).
- App responds: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3100/TCGEngine/SharedUI/LoginPage.php` → `200`.
- DB reachable (only if you'll touch data): `docker exec otmtcge-swustats-mysql-server-1 mysql -u root -psecret -D swudeck -e "SELECT 1"`.

If something's down, tell the user and offer to start it (`docker compose up -d`) — don't guess
around a broken environment.

### 3. Ready the cross-browser verification tooling

Per the CLAUDE.md engineering rule, SWUDeck UI/CSS changes must be checked in **Chromium +
Firefox + WebKit**. Make sure verification is one command away later:

- `DevTools/ui-harness/` exists with `node_modules` installed — if not, `cd DevTools/ui-harness && npm install`
  (its postinstall pulls the three browser engines). Usage + flags are in its `README.md`.
- Test login is `Drixx` / `pass` (CLAUDE.md `## Creds`); the users table username column is
  `usersUid`. Mutating flows (leader swap/remove, import) run on a **throwaway** deck, never a
  real one.

Don't run a full render yet — just confirm nothing blocks it.

### 4. Surface in-flight work

- `git status` + `git log --oneline -5` — uncommitted changes, current branch, recent commits.
  Standing rule: **the user commits, never the assistant.**
- Re-state any open follow-ups found in step 1 so they aren't silently dropped.

### 5. Confirm the task

Briefly restate what the user wants to do this session, flagging anything from the above that
changes the approach. Then proceed.

## Output

A short orientation summary: the relevant carried-forward gotchas, environment status (up / what's
down), tooling readiness, in-flight/uncommitted state + open follow-ups, and the confirmed task.
