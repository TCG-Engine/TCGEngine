# UI regression suites

Standing Playwright checks for behaviors that have broken before. Each file is a self-contained
script that logs `PASS`/`FAIL` per assertion.

Suites are grouped into **per-rootname folders** — one folder per app (`SWUDeck/`, and `SWUSim/`
etc. as they're added). All seven current suites are `SWUDeck` (the deck-viewer UI); they do not
cover the SWUSim game board.

```
regression/
  lib.mjs              # shared harness helpers (login, navigation, waits, checker)
  run-all.mjs          # runs every suite in every rootname folder
  SWUDeck/*.mjs        # the SWUDeck suites
```

```bash
cd DevTools/ui-harness
node regression/run-all.mjs               # every rootname
node regression/run-all.mjs SWUDeck       # just one rootname
node regression/SWUDeck/touch-preview.mjs # one suite
```

Requires the local stack up (`http://localhost:3100/TCGEngine`, override with `BASE=`) and the
`Drixx` test login (CLAUDE.md `## Creds`). Suites log in themselves.

**WebKit on macOS 14** (this box): Playwright ≥1.62 ships a frozen WebKit whose driver handshake
fails, so `newPage()` hangs — it looks like "WebKit is broken here", and it is not. Side-install
`playwright@1.52.0` and point the harness at it (the install lives outside the repo, so expect it
to be gone next session; re-creating it takes ~3 minutes):

```bash
mkdir -p /tmp/pw152 && cd /tmp/pw152 && npm init -y && npm install playwright@1.52.0
PLAYWRIGHT_WEBKIT_MODULE=/tmp/pw152/node_modules/playwright \
  node regression/run-all.mjs SWUDeck
```

Suites that read `ENGINES=` (the three newest) take a comma-separated subset, e.g.
`ENGINES=firefox,webkit`.

## Suites (`SWUDeck/`)

| File | Covers |
|---|---|
| `card-touch-suppression.mjs` | `SharedUI/css/card-touch.css` is linked, cache-busted, applies to card images; `contextmenu` cancelled on cards but not on `<body>`. |
| `touch-preview.mjs` | Long-press preview renders the full `WebpImages` card, fits the viewport, is centered, persists past `touchend`, dismisses on next tap without mutating the deck. Also asserts **desktop hover is unchanged** (400px cap, no scrim). |
| `repeat-preview.mjs` | A *second* long-press survives the synthetic `mouseout` that touch platforms fire when the finger moves between cards. |
| `preview-stability.mjs` | After a long (>2s) hold the preview stays stably visible — samples visibility 12x, so flicker can't hide between snapshots — and does not intercept pointer events. |
| `touch-drag-suppression.mjs` | `dragstart` is prevented on coarse-pointer devices (no yellow `.droppable` borders) but **still allowed on desktop**. |
| `leader-tab-visibility.mjs` | Premier decks never show `Leader1`/`Leader2`; Twin Suns decks never show `Leaders` — including after pane switches, which re-render the tabs. |
| `library-scroll-persistence.mjs` | The mobile library keeps its scroll position when you tap a card to add it. **Mutates a deck** (a tap adds a card), so it runs against scratch deck `900001` and restores its `Gamestate.txt` afterwards — never point `GAME` at a real deck. Its slow-image route is load-bearing: with a warm cache the bug does not reproduce at all. |
| `card-tile-height.mjs` | The shared `Card()` renderer emits a tile height with a **unit** (it once emitted `height:87`, invalid and silently dropped, so lazy tiles had no height until their image loaded). Asserted on **desktop**, on the Cards pane — mobile and the Leaders/Bases panes deliberately override the height with `height:auto !important`. |
| `preview-controls.mjs` | The touch preview's **X close** and the **leader flip** to the deployed Leader Unit side (`<CardID>_back`). Pins that an ordinary card gets NO flip, that controls are exempt from BOTH tap-to-dismiss and the post-long-press click suppressor (either one silently makes a rendered button dead), and that desktop hover is unchanged. |
| `mobile-art-paths.mjs` | Every **client-built** art URL on the mobile board (identity banner, "recently added" strip) comes from the shared-corpus seam `window.swuCardArtUrl`, and each image actually **decoded** — a correct-looking path can still 404. Also fails if any request hits a per-app art tree (`/SWUDeck/concat` etc.). **Mutates** scratch deck `900001`, restores its Gamestate. |
| `mobile-clipboard.mjs` | The deck menu's **Copy Text / Copy JSON / Copy Image** actually reach the clipboard on WebKit (every iOS browser), not just Chromium/Firefox. Pastes back to check the real symptom, **and** asserts each clipboard call is issued inside the click turn — the user-activation rule WebKit enforces but Playwright's WebKit does not, so an "await the payload first" refactor can't silently re-break iOS. Also asserts the menu no longer flashes an unconditional "copied!". |

## Shared harness (`lib.mjs`) — the de-brittling rules

Every suite builds on `lib.mjs` instead of copy-pasting setup. Two rules it enforces:

- **Condition-based waits, never fixed sleeps.** `openBoard()` navigates and then
  `waitForFunction`s until a card has actually rendered — so a slow load can't produce a false
  `FAIL`. (The old suites slept a flat `waitForTimeout(3500)`.)
- **Fail loud on a not-ready environment.** A down stack / missing test deck / failed login throws
  `EnvError`, which `harness()` reports as **`ENVIRONMENT NOT READY`** and exits **2** — distinct
  from a real regression (exit **1**). So a red result is trustworthy, and `run-all` won't call a
  setup problem a regression.

Exit codes: **0** all passed · **1** a check FAILED (regression) · **2** environment not ready.

## Test decks

Defaults: `GAME`/`TWINSUNS` = `201009` (twinsuns, two leaders), `PREMIER` = `100431` (premier,
single leader). Override via env:

```bash
PREMIER=100431 TWINSUNS=201009 node regression/SWUDeck/leader-tab-visibility.mjs
GAME=201009 node regression/SWUDeck/touch-preview.mjs
```

A deck id only loads if `SWUDeck/Games/<id>/` exists — most `ownership` rows have no folder and
render "Game does not exist". If a deck is missing the suite now exits **2 (ENV)** with a clear
message rather than a confusing `FAIL`. Check `ls SWUDeck/Games` before swapping ids.

## What these CANNOT verify

**Playwright's WebKit does not implement iOS's native gesture layer** — the long-press image
callout, the image-lift animation, or drag-initiation-from-long-press. During the work these
suites came from, a clean 3-engine run coexisted with the bug being fully present on a real
iPhone.

So `card-touch-suppression.mjs` asserts the *rule is applied and the file declares the property* —
it cannot assert the callout is actually suppressed. **Any change to touch gesture handling needs
sign-off on a physical iOS device.** Treat green here as "worth testing", not "verified". The
touch suites (`touch-preview`, `repeat-preview`, `preview-stability`, `touch-drag-suppression`)
DO catch the JS-logic regressions (event handlers, geometry, CSS wiring); only the native gesture
itself is out of reach. `leader-tab-visibility` and the desktop-hover half of `touch-preview` are
fully verifiable.

## Adding a new rootname (e.g. SWUSim)

Create `regression/<Rootname>/` and drop suites there. Reuse `lib.mjs` — `openBoard(page, { game,
folderPath: '<Rootname>', mobile })` already takes the folderPath. `run-all.mjs` discovers new
rootname folders automatically.

Related memory: `playwright-cannot-verify-native-touch-gestures`,
`swudeck-mobile-layout-dom-gotchas`.
