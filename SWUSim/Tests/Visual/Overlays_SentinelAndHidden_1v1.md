# VISUAL CHECK — Sentinel overlay is more solid, and the image overlays are brighter (1v1 board)
#
# Visual-only schema. Lives under Tests/Visual/, which the regression endpoint does NOT scan, so nothing here is
# asserted by the suite. Load it in the Test Schema Editor and Run all, or run the automated 3-engine pass:
#   node DevTools/ui-harness/swusim-overlay-brightness-xbrowser.mjs     (Chromium + Firefox + WebKit, desktop AND mobile)
#
# WHAT CHANGED (2026-10-23, player feedback: "the Sentinel overlay is too transparent" and "overlays in general are not
# bright enough"):
#   1. The Sentinel wall (Assets/Overlays/tech-wall.webp) is drawn at opacity 0.88, up from 0.71 (Schemas/SWUSim/GameSchema.txt,
#      both arenas' `Overlay: HasSentinel` lines; regenerate after editing).
#   2. Every IMAGE overlay — the Sentinel wall and the Hidden smoke (smoke-overlay.webp) — gets a light brightening filter,
#      `filter: brightness(1.2)` (SWUSim only: GameLayoutShared.php, class .card-image-overlay added by the shared renderer in Core/UILibraries).
#      The EXHAUSTED dimming (the dark .exhausted-status-overlay-layer) is NOT brightened — it exists to darken.
#
# What to look at (as P1, after Run all):
#   a. My ground arena: SOR_229 Cell Block Guard and my space arena: SOR_063 Cloud City Wing Guard — the tech wall is clearly
#      visible across each card (before: washed out), and its metal reads lighter than before.
#   b. Their ground arena: their own Cell Block Guard — the same wall.
#   c. Their ground arena: HMW_162 Teebo, played THIS phase by P2 (the WHEN line), so he is Hidden — the smoke reads lighter/whiter
#      than before, the card art still visible through it.
#   d. Negative: their EXHAUSTED Cell Block Guard (index 1) keeps the same DARK exhausted tint as before — brighter would be the bug.
#   Mobile (?swuLayout=mobile): the same overlays, the same opacity and brightness.
#
# BOARD SHAPE: Sentinels in BOTH arenas and on both sides (the rule lives on both `Overlay: HasSentinel` lines); one exhausted
# Sentinel for the negative control; P2 is active so it can play Teebo — Hidden only shows on a unit played this phase.

## GIVEN
CommonSetup: rrk/rrw/{myResources:6; theirResources:4}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP1GroundArena: SOR_229:1:0
WithP1SpaceArena: SOR_063:1:0
WithP2GroundArena: [SOR_229:1:0 SOR_229:0:0]
WithP2Hand: HMW_162

## WHEN
- P2>PlayHand:0

## EXPECT
