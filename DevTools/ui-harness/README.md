# UI Harness — cross-browser snapshot & measure

Reusable Playwright harness so verifying a UI change across **Chromium + Firefox + WebKit** is one
command instead of a throwaway script each session. Per the CLAUDE.md engineering rule, UI/CSS
changes must be checked in all three engines before sign-off — layout diverges between them (the
recurring example: `height:100%` resolves against a flex-stretched parent in Chromium but not
Firefox/WebKit).

## Setup (once)

```bash
cd DevTools/ui-harness
npm install          # postinstall pulls the chromium, firefox, and webkit browser engines
```

`node_modules/` is gitignored — this is a local dev tool, not shipped.

## FaBSim priority shortcuts

```bash
node fab-shortcuts-xbrowser.mjs [SCREENSHOT_DIR] [chromium,firefox,webkit]
php ../FaB/shortcuts_test.php
```

The browser fixture loads the real FaBSim script, styles, and shortcut registry with local settings
and transport adapters. It checks master hold, preserved selections, reload persistence, keyboard
and dismissal behavior, and desktop/mobile layout. The PHP suite checks authoritative auto-pass
behavior, including multiplayer seats. The browser fixture does not connect to a live match.

## Latest-printing display

```bash
node latest-printing-xbrowser.mjs --game <deckID> --canonical <storedId> --display <shownId>
```

Confirms a reprinted card RENDERS its newest standard printing while its stored identity stays
canonical, in all three engines. Exits non-zero on failure.

⚠ Pass the values the `<img src>` actually carries, and pick a card that is in the deck's **main deck**
— not merely present somewhere in `Gamestate.txt`, which also holds the browse-pane card pool that the
board never renders. Legacy decks store FFG UUIDs, so `--canonical` is often a UUID while `--display`
is a SET_NNN id; that asymmetry is the point, and it is what caught the missing UUID normalisation in
`SWUDisplayCardID`.

## Card Search applet (main menu)

```bash
node card-search-xbrowser.mjs                 # defaults to the local SWUDeck main menu
node card-search-xbrowser.mjs <url> <outPrefix>
```

Exercises the whole applet in all three engines: lazy bundle fetch, autofocus, the deckbuilder filter
syntax (`f=premier`, `c:rr`, `cost>=8 is=unit`), and the zero-result path. Exits non-zero on failure,
so it is usable as a gate. The lazy-fetch assertions are the load-bearing ones — an eager `<script>`
tag would double the main menu's weight and no functional test would notice.

## SWUSim game board — boards built from a Visual schema (2026-09-11)

```bash
node swusim-log-styles-xbrowser.mjs [BASE] [SHOTS_DIR]      # game-log line styles, desktop + mobile layout
node swusim-refill-slide-xbrowser.mjs [BASE]                # the Smuggle-refill deck → resources slide
node swusim-resource-filter-xbrowser.mjs [BASE] [SHOTS_DIR] # resource box narrows to the offered resources
```

Both build their board from a `SWUSim/Tests/Visual/*.md` schema through the Test Schema Editor's own
endpoints (`SWUSim/TestSchemaSetup.php`, `TestSchemaStep.php`) — mod login, `claudebot1` — then open
`NextTurn.php?…&authKey=testschema` in Chromium, Firefox and WebKit. Exits non-zero on failure.
- **log-styles:** `GameLog_UndoneAndDeckLines.md`. The `(undone)` line (type `UNDONE`) is dimmed and struck
  through, and the live lines are not (a negative control). The search's pick line is `REVEAL` gold; its
  "put N on the bottom" line is `DECK`. Log-panel screenshots go to `SHOTS_DIR`.
- **resource-filter:** `ResourceBox_ShowsOnlyOfferedCredits.md`. A decision that offers resources auto-opens
  the resource box; it must then render ONLY the resources in the offer. The board interleaves two Credits
  among five ordinary resources and leaves Han Solo's "[defeat a friendly token]" cost pending, so the box
  must show exactly the two Credits and title itself "SELECTABLE RESOURCES". Each run also builds the SAME
  board WITHOUT executing the WHEN step — no decision, box opened by hand, all seven resources — which is
  what proves the narrowing follows the offer rather than always hiding non-Credits.
- **refill-slide:** `ResourceTopOfDeck_SmuggleRefillSlides.md`. ⚠ The STEP endpoint stubs every animation, so
  the Smuggle is performed through the page's own `SubmitInput` instead (the resource-click path, real
  animations). The script checks for a `ZONE_MOVE` `p1Deck → p1Resources` scoped to seat 1, plus a
  `.tcg-zone-move-clone` on the page.

## Usage

Render a SWUDeck deck's identity banner in all three engines, screenshot it, and measure the leader
/ base images (auto-logs in as `Drixx` — see CLAUDE.md `## Creds`):

```bash
node snap.mjs --game 201009 --selector '#swuIdentityBanner' \
    --measure '#myLeaderSlot img,#myBaseSlot img' --out /tmp/banner
```

Writes `/tmp/banner-chromium.png`, `-firefox.png`, `-webkit.png`, prints each element's box +
`display`/`object-fit`/`height` per engine, and **flags any cross-engine height mismatch** at the
end (the usual smell for a broken percentage-height chain).

Arbitrary URL, single engine, explicit login:

```bash
node snap.mjs --url http://localhost:3100/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php \
    --engines chromium --login --selector '.swu-deck-stack-frame'
```

## Flags

| Flag | Default | Purpose |
|---|---|---|
| `--game <id>` | — | SWUDeck deck gameName → opens the editor; implies `--login`. |
| `--url <url>` | MainMenu | Arbitrary page (mutually exclusive with `--game`). |
| `--engines a,b,c` | all three | Subset of `chromium,firefox,webkit`. |
| `--selector <css>` | full page | Element to screenshot + anchor measurements to. |
| `--measure <css,...>` | — | Extra selectors to measure (box + display/object-fit/height). |
| `--out <prefix>` | `/tmp/uisnap` | Screenshot prefix → `<prefix>-<engine>.png`. |
| `--login` | off (on with `--game`) | Log in before navigating. |
| `--user` / `--pass` | `Drixx` / `pass` | Test creds (override for other users). |
| `--base <url>` | `http://localhost:3100/TCGEngine` | Base URL. |
| `--viewport WxH` / `--dpr n` | `1600x950` / `2` | Viewport + device scale. |
| `--wait <ms>` | `1500` | Settle delay after navigation. |

## Regression suites

`snap.mjs` is for ad-hoc inspection. Standing checks for behaviors that have broken before live in
[`regression/`](regression/README.md) — mobile long-press preview, iOS callout/drag suppression,
and leader-tab visibility per deck format:

```bash
node regression/run-all.mjs        # all suites
node regression/touch-preview.mjs  # just one
```

Note their limits: Playwright cannot verify iOS **native** gesture interception, so touch work
still needs sign-off on a physical device. See that README's "What these CANNOT verify".

## Notes

- Mutating flows (leader swap/remove, import) should run against a **throwaway** deck, not a real
  one — the editor autosaves. This harness only navigates + reads by default.
- Deck ids only load if `SWUDeck/Games/<id>/` exists. Known-good: `100431` (premier, single
  leader), `201009` (twinsuns, two leaders).
- Related memory: `verifying-swudeck-ui-cross-browser`, `css-percentage-height-flex-gotcha`,
  `playwright-cannot-verify-native-touch-gestures`, `swudeck-mobile-layout-dom-gotchas`.
