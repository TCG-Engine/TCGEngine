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

## Notes

- Mutating flows (leader swap/remove, import) should run against a **throwaway** deck, not a real
  one — the editor autosaves. This harness only navigates + reads by default.
- Related memory: `verifying-swudeck-ui-cross-browser`, `css-percentage-height-flex-gotcha`.
