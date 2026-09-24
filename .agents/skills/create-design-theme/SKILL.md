---
name: create-design-theme
description: Use when creating a new design-system theme for the SWU sites — a new SharedUI/Themes/<name>.tokens.css that reskins buttons/inputs/panels/dialogs, including signature styled-button themes (chamfer / octagon / slash geometry + honeycomb/ember/scanline textures), or adding a theme to the zzDesignSystemPreview.php preview. Not for editing shared components.
---

# Create Design Theme

## Overview

A theme is a **token-only `:root` set** in `SharedUI/Themes/<name>.tokens.css`. It never
contains component CSS — it only feeds values to the shared components in
`SharedUI/css/components.css`. Any app adopts a theme by listing it in its `SiteDef` styles
array; every theme is previewable in `zzDesignSystemPreview.php?theme=<name>`.

Prereq: the docker stack running (`http://localhost:3100`). Do NOT run host PHP — verify via curl.

## Workflow

1. **Gather.** Ask the user (one message) for: the **name** (kebab-case, e.g. `molten-forge`),
   a **brief description / vibe**, and an **optional reference image**. Wait for the answer.
2. **Build.**
   - Create `SharedUI/Themes/<name>.tokens.css` — a full token set (copy the closest existing
     theme as a template, then adjust; see Templates + Token Reference).
   - Add it to the `$themeFiles` map in `zzDesignSystemPreview.php`.
   - Verify: theme file serves 200; `?theme=<name>` links it; existing themes unchanged (curl).
3. **Review.** Ask the user to open `http://localhost:3100/TCGEngine/zzDesignSystemPreview.php?theme=<name>`
   (the harness renders buttons + semantics + inputs + checkbox + dropdown + tabs + table + panel +
   dialogs). This is the acceptance gate — you can't see textures via curl.
4. **Adjust** per their feedback; re-verify; repeat step 3 until they sign off.

## Templates — copy the closest, then adjust

| Want | Copy from |
|---|---|
| Flat/rounded gradient buttons | `clarent.tokens.css` |
| Chamfered HUD buttons | `hud.tokens.css` |
| Rectangle + textured fill | `molten-forge.tokens.css` |
| Octagon (4 cut corners) + texture | `circuit-sigil-cyan.tokens.css` / `-gold.tokens.css` |
| Slashed corners + texture | `infernal-edge.tokens.css` |

**Recoloring a template:** keep its alpha/opacity structure and surface family (the navy-family
themes share `--surface`/`--surface-raised`/etc.); swap the accent hue, and only override the
surfaces/text if the vibe genuinely differs. Set only the tokens you actually change.

## Token reference

**Every token is optional** — a theme sets only what it changes; anything unset inherits the
neutral defaults in `tokens.css`. Two tiers:
- **Flat theme** (rounded/gradient buttons, like Clarent): set just the **palette** below. Skip
  all the button geometry/texture tokens and the semantic-gradient/plain-fill block — the shared
  `.btn` handles primary (solid `--accent`) and inherits the neutral danger/success surfaces.
- **Signature/textured theme** (chamfer/octagon/slash + honeycomb/ember/etc.): palette **plus**
  the button geometry + texture tokens **plus** the semantic-gradient + plain-fill block.

**Role tokens (whole-app palette):** `--surface`, `--surface-raised`, `--surface-sunken`,
`--border`, `--text`, `--text-muted`, `--accent`, `--accent-strong`, `--on-accent`, `--glow`,
`--radius`, `--danger`, `--success`, `--danger-surface`, `--success-surface`, `--on-danger`,
`--on-success`, `--check-fill`, `--check-mark`, `--card-border`, `--font-display`, `--input-text`
(only if the input field wants text unlike `--text`, e.g. a light field).

**Button — base:** `--btn-chamfer-content: ''` (turns the rim/fill pseudo-layers ON — required
for any styled button), `--btn-bw`, `--btn-surface` (keep `transparent` when pseudos are on),
`--btn-shadow`, `--btn-text`, `--btn-transform`, `--btn-tracking`, `--btn-pad`,
`--btn-hover-transform`, `--btn-glow`, `--btn-glow-color`, `--btn-rim-width`.

**Button — geometry** (pick one):
- Chamfer/rectangle: `--btn-cut` / `--btn-cut-inner` (0 = rectangle). Uses the default polygon.
- Custom (octagon, slash, …): `--btn-clip` (`::before` rim polygon) + `--btn-clip-inner`
  (`::after` fill polygon), each a full `polygon(...)`.

**Button — texture:** `--btn-rim` (`::before` background — the rim gradient), `--btn-fill`
(`::after` background — the fill; can be a multi-layer stack incl. a colored honeycomb SVG
data-URI, ember/scanline gradients, and a navy base), `--btn-fill-blend`
(`background-blend-mode`, per layer), `--btn-fill-size` (`background-size`, for SVG tiling),
`--btn-after-shadow` (inner neon frame `box-shadow`), `--btn-anim` (e.g.
`emberpulse 2.6s ease-in-out infinite` — `@keyframes emberpulse` is defined in components.css).

**Semantic + plain fills — SIGNATURE/textured themes only** (flat themes skip these; their
`.btn-primary` uses solid `--accent` and danger/success inherit the neutral tinted defaults):
- `--accent-surface` = a **gradient** for `.btn-primary` (else primary is a flat solid).
- `--danger-surface` / `--success-surface` = **gradients** (else danger/success are flat).
- `--btn-plain-fill` = a plain fill (no honeycomb) — used by **input-type buttons and tabs** so
  they don't inherit the busy `--btn-fill` texture.

## Gotchas (learned the hard way)

- **Cut-geometry themes MUST set `--btn-clip`.** The base `.btn` clips its *element* to
  `var(--btn-clip)`; without it, the `.btn-primary/success/danger` element fill is a rectangle
  that **bleeds past** the octagon/slash. (Chamfer-via-`--btn-cut` themes have only a tiny bleed;
  octagon/slash themes must set `--btn-clip`.)
- **Honeycomb/texture colors are baked into the SVG data-URI** — a theme provides its own
  colored `%23<hex>` stroke SVG in `--btn-fill`; there is no cross-theme recolor.
- **Semantic variants reset `--btn-fill-blend`/`--btn-fill-size` to normal/auto** in
  components.css, so a single-layer gradient `--*-surface` renders cleanly. Good.
- **Themes are `:root` token sets only** — never put component/element CSS in a theme file. Put
  genuinely site-specific rules in `Sites/<App>/css/<app>-overrides.css`.
- **App-selectable:** to wire an app to a theme, its `SiteDef` styles array loads
  `tokens.css → components.css → Themes/<name>.tokens.css → <app>-overrides.css`.

## Verify

```
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3100/TCGEngine/SharedUI/Themes/<name>.tokens.css   # 200
curl -s "http://localhost:3100/TCGEngine/zzDesignSystemPreview.php?theme=<name>" | grep -o "<name>.tokens.css"  # linked
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3100/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php   # 200 = existing themes unregressed
```
Then the user eyeballs the preview — that's the real acceptance gate.
