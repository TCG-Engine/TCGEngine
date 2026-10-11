# SharedUI CSS

## Browser support floor

**Chromium 111+, Firefox 128+, Safari 16.4+.**

That is the floor for everything the design system relies on: `oklch()`,
`color-mix()`, cascade layers (`@layer`), `:has()`, container queries and
`@property`. Verified with `DevTools/ui-harness` against Chromium, Firefox and
WebKit. There is no browserslist and no build step — this file IS the record.

## Two traps, both verified 2026-09-24

### 1. Nested `color-mix()` crashes the WebKit renderer

A `color-mix()` may have **at most ONE operand that is itself a `color-mix()`**.

| form | Chromium | WebKit |
|---|---|---|
| `color-mix(A, B)` — plain colours | ok | ok |
| `color-mix(mix, B)` — one mix operand | ok | **ok** |
| `color-mix(mix, mix)` — both operands mixes | ok | **CRASH** |
| depth 3 | ok | **CRASH** |

Not a mis-paint — the renderer process dies and the page is blank. It makes no
difference whether the inner mixes are inline or held in `var()`. Build token
ramps one level at a time:

```css
/* safe */
--step-1: color-mix(in oklch, var(--base) 80%, var(--accent));
--step-2: color-mix(in oklch, var(--step-1) 50%, white);

/* kills Safari */
--bad: color-mix(in oklch, var(--step-1) 50%, var(--step-2));
```

### 2. Backdrop roots silently kill `backdrop-filter`

`container-type`, `filter`, `clip-path`, `isolation: isolate` and **any z-index
stacking context** make an element a *backdrop root*. Every frosted descendant
then blurs nothing — in all three engines, with no error and no warning.

This is why `swusim-overrides.css` warns "never put a `filter` on an ancestor of
the glass". The rule is broader than that comment: `container-type` does it too,
which is a live hazard because container queries and frosted panels are both
things this design system wants.

If a frosted surface suddenly renders flat, look up its ancestor chain for one of
those five properties before looking at the blur itself.

## Cascade layers and the migration

New CSS goes in `@layer` (see `system.css`). **Unlayered CSS beats layered CSS**,
so all the legacy stylesheets keep winning by default and cannot be broken by
anything added to a layer. Files migrate into layers one at a time, deliberately.
That property is the whole reason the migration can be incremental.

## Verifying a change

```
# CSS load order and render contracts
curl http://localhost:3400/TCGEngine/SharedUI/Render/Tests/RunRenderTests.php

# cross-browser, per surface
cd DevTools/ui-harness && node <surface>-xbrowser.mjs
```

Two measurement traps that have produced false results here:

- **Lazy images.** `loading="lazy"` images below the fold are not decoded when you
  screenshot, so they read as broken and a pixel diff of two captures disagrees.
  Force `loading='eager'` and `await img.decode()` before judging.
- **Pixel-diffing a live page.** The SWUSim menu rotates a tip every 8s and polls
  games every 20s. Two captures seconds apart differed by 63% with no code change
  between them. Compare *geometry*, not pixels, unless the page is frozen.
