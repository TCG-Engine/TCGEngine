# VISUAL CHECK — Arena HUD frame pulse (opacity-only glow layer)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load it in the Test
# Schema Editor, on desktop AND with ?swuLayout=mobile.
#
# WHAT CHANGED (2026-10-09): the cyan HUD frame around each arena used to pulse by animating
# border-color + box-shadow on the arena-sized frame and filter:drop-shadow on its corner brackets,
# forever. None of those run on the compositor, so both frames were repainted on every display refresh,
# idle or not: measured ~75% of a core on an idle board with GPU acceleration and ~240% without it
# (a "fans spin up immediately" report). Now the frame + brackets are STATIC at the pulse's dimmest
# values and a pre-drawn `.swu-arena-glow` child holds the brightest look and animates OPACITY only.
# Desktop: GameLayout.php (.swu-arena-bg / .swu-arena-glow). Phone: GameLayoutMobile.php
# (.swu-m-arena-col / .swu-arena-glow). After: ~52% / ~106% on the same benchmark board.
#
# What to look at (let it run for a few seconds; one pulse is 3.2s):
#   1. Both arena frames still visibly breathe: border, outer glow and the corner-bracket glow brighten
#      together and fade back. A frame that never changes means the glow child is missing or hidden.
#   2. At the dimmest point the frame looks exactly as before: faint border, faint glow, brackets solid.
#      The brackets must NEVER fade out — only their glow pulses.
#   3. NO vertical line on the INNER edge beside the leader/base column, at ANY point in the pulse. The
#      glow layer repeats the per-side border-right:0 / border-left:0 and side-offset shadows; if a line
#      appears only at the peak, those overrides were lost.
#   4. Cards sit ABOVE the glow (it must not tint card art), and pointer clicks on cards still work
#      (the glow is pointer-events:none).
#   5. Phone (?swuLayout=mobile): all FOUR arena boxes pulse; the "Their Space" / "My Ground" labels
#      are unchanged and still first; the cards scroll normally inside each box.
#   6. Twin Suns home view: the frame covers only YOUR half (body.swu-home .swu-arena-bg) and still pulses.
#   7. Gear menu → "Reduce board animations" (added 2026-10-09, per-browser, GameLayoutShared.php
#      body.swu-board-calm): ticking it STOPS both the arena glow (frame sits at its dim look) and the
#      turn-indicator glyph pulse (glyphs stay fully visible, just still). It survives a reload, unticking
#      brings both pulses back, and a browser with OS "reduce motion" on starts with it ticked. Nothing
#      else on the board changes. Measured idle board: 48% → 8% of a core (GPU on), 104% → 6% (GPU off).
#
# How it was measured (re-run after any change to these frames):
#   • Chromium DevTools → Performance → record 10s idle: no recurring "Paint" for the arena frames.
#   • Or Rendering → "Paint flashing": the arena frames must NOT flash green while idle.
#   • document.getAnimations() on the board lists swuArenaGlow (desktop) / swuMArenaGlow (phone), and no
#     swuArenaPulse*/swuArenaBracketPulse/swuMArenaPulse/swuMArenaBracketPulse.
#
# BOARD SHAPE: both arenas populated on both sides (6 ground + 3 space each) so the frame is drawn
# around real cards; an upgrade and a Shield are on P1's units so card-layer stacking is visible; P1 has
# a Sentinel (SOR_063) so its animated overlay is on screen too (that overlay is a separate cost, not
# part of this change).
#
# Cross-browser: verified in Chromium, Firefox and WebKit (desktop 1440×900 and phone 390×844) by
# pixel-diffing frames frozen at the pulse trough (identical) and peak (<1.1% of pixels, max Δ 50/255).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;theirResources:8}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:1 LAW_180:1:0 SOR_063:1:0 SOR_095:1:2 SOR_046:1:0]
WithP1SpaceArena: [SOR_237:1:0 JTL_069:1:0 SOR_237:1:1]
WithP2GroundArena: [SEC_080:1:0 SOR_128:1:0 LAW_124:1:2 SEC_080:1:1 SOR_046:1:0 LAW_124:1:0]
WithP2SpaceArena: [SOR_225:1:0 JTL_069:1:3 SOR_225:1:0]
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_T02

## WHEN

## EXPECT
P1GROUNDARENACOUNT:6
P1SPACEARENACOUNT:3
P2GROUNDARENACOUNT:6
P2SPACEARENACOUNT:3
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
