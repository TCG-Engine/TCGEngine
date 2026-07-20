# UI regression suites

Standing Playwright checks for SWUDeck behaviors that have broken before. Each file is a
self-contained script that logs `PASS`/`FAIL` per assertion and exits non-zero on failure.

```bash
cd DevTools/ui-harness
node regression/run-all.mjs               # everything
node regression/touch-preview.mjs         # one suite
```

Requires the local stack up (`http://localhost:3100/TCGEngine`) and the `Drixx` test login
(CLAUDE.md `## Creds`). Suites log in themselves.

## Suites

| File | Covers |
|---|---|
| `card-touch-suppression.mjs` | `SharedUI/css/card-touch.css` is linked, cache-busted, applies to card images; `contextmenu` cancelled on cards but not on `<body>`. |
| `touch-preview.mjs` | Long-press preview renders the full `WebpImages` card, fits the viewport, is centered, persists past `touchend`, dismisses on next tap without mutating the deck. Also asserts **desktop hover is unchanged** (400px cap, no scrim). |
| `repeat-preview.mjs` | A *second* long-press survives the synthetic `mouseout` that touch platforms fire when the finger moves between cards. |
| `preview-stability.mjs` | After a long (>2s) hold the preview stays stably visible — samples visibility 12x, so flicker can't hide between snapshots — and does not intercept pointer events. |
| `touch-drag-suppression.mjs` | `dragstart` is prevented on coarse-pointer devices (no yellow `.droppable` borders) but **still allowed on desktop**. |
| `leader-tab-visibility.mjs` | Premier decks never show `Leader1`/`Leader2`; Twin Suns decks never show `Leaders` — including after pane switches, which re-render the tabs. |

## Test decks

Defaults: `GAME`/`TWINSUNS` = `201009` (twinsuns, two leaders), `PREMIER` = `100431` (premier,
single leader). Override via env:

```bash
PREMIER=100431 TWINSUNS=201009 node regression/leader-tab-visibility.mjs
GAME=201009 node regression/touch-preview.mjs
```

A deck id only loads if `SWUDeck/Games/<id>/` exists — most `ownership` rows have no folder and
render "Game does not exist". Check `ls SWUDeck/Games` before swapping ids.

## What these CANNOT verify

**Playwright's WebKit does not implement iOS's native gesture layer** — the long-press image
callout, the image-lift animation, or drag-initiation-from-long-press. During the work these
suites came from, a clean 3-engine run coexisted with the bug being fully present on a real
iPhone.

So `card-touch-suppression.mjs` asserts the *rule is applied and the file declares the property* —
it cannot assert the callout is actually suppressed. **Any change to touch gesture handling needs
sign-off on a physical iOS device.** Treat green here as "worth testing", not "verified".

Related memory: `playwright-cannot-verify-native-touch-gestures`,
`swudeck-mobile-layout-dom-gotchas`.
