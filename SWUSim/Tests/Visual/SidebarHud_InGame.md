# VISUAL CHECK — the in-game right sidebar as a New Petranaki HUD panel
#
# Visual-only (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP
#   GN=$(curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   Speak into it from either seat without a second browser:
#     fetch('./SubmitChat.php?gameName='+GN+'&playerID=2&authKey=testschema&folderPath=SWUSim&chatText=hi')
#     (you must be LOGGED IN to chat — owner ruling 2026-09-21.)
#
# WHAT THIS PINS
#   Owner request 2026-09-22: "i want this gamelog+chat to also be the new style", with the WHOLE
#   sidebar as one glass panel rather than a card nested in a flat column.
#   ⚠ The in-game board was DELIBERATELY not migrated in the 2026-09-21 pass (see the memory note
#   new-petranaki-hud-style, "NOT migrated: … the in-game board"). This is that decision revisited for
#   the sidebar only — the board, the arenas and the card rendering are untouched.
#
# ★ WHERE THE GLASS COMES FROM, AND WHY IT MOVED
#   SharedUI/Sites/SWUSim/css/petranaki-glass.css — the tokens (--pa-cut / --pa-line / --pa-glass /
#   the glow SVGs / --pa-glass-clip) plus a reusable `.pa-glass` class. NextTurn.php <link>s it for
#   SWUSim; SiteDef lists it immediately before swusim-overrides.css for site pages.
#   It was EXTRACTED from swusim-overrides.css on 2026-09-22 because the board never loads that file,
#   and the alternative was copying the recipe — which is exactly how a 14px cut and a 90×90 glow
#   drift apart. ⚠ The per-surface selector lists (.wr-panel, the site panels, #tcg-chat-panel) stay
#   in swusim-overrides.css on purpose: the board loads the partial, and site-page selectors have no
#   business running on a game board.
#
# ★★ TWO THINGS THE STOCK RECIPE GETS WRONG ON THIS SURFACE
#   1. THE FILL INVERTS. --pa-glass is tuned for the sandy arena backdrop of the SITE pages, where a
#      translucent stone-grey DARKENS what is behind it. The board is near-black, so the same fill
#      LIGHTENS it — the first cut came out PALER THAN THE TABLE beside it and read as washed-out grey
#      rather than a HUD panel. #swuSidebar overrides the --pa-glass token (and softens the
#      brightness(0.82) backdrop step, which only muddied a dark fill). Overriding the TOKEN is the
#      documented per-surface escape hatch; the chamfer, hairline, glow and shadow still come from
#      .pa-glass.
#   2. A BOTTOM-LEFT GLOW NEEDS ITS OWN ARTWORK. The shipped glows are drawn per corner:
#      --pa-glow-tl's stroke runs UP the left edge, across the cut and along the top. Re-using it at
#      `bottom left` — the obvious shortcut, and the first cut — draws that stroke the wrong way round
#      and paints a gold WEDGE in the corner, clearly visible on a full screenshot. #swuSidebar
#      defines --pa-glow-bl: the bottom-RIGHT artwork mirrored (gradient origin and path moved left).
#
# ★★ WHY THE CORNERS LOOKED SQUARE (reported THREE times, 2026-09-22) — AND IT WAS NEVER THE CLIP
#   A chamfer does not paint a corner, it REMOVES one, so what you see in the cut is whatever sits
#   BEHIND the panel. .theirStuffWrapper / .myStuffWrapper and their #theirStuff (rgb 42,42,42) /
#   #myStuff (rgb 30,30,30) layers all ran the FULL viewport width — under a sidebar starting at
#   var(--swu-board-w) — although every other board element is held to right: var(--swu-sidebar-w).
#   The cut revealed board-grey against a grey panel: invisible.
#   FIX THE BACKDROP, NEVER THE CUT:
#     .theirStuffWrapper, .myStuffWrapper { width: var(--swu-board-w) !important; }
#   Safe because the sidebar is position:fixed (a DOM child of .myStuffWrapper, but a parent's width
#   cannot move a fixed element) and because those four are pure backdrop panels — #theirStuff /
#   #myStuff hold only a <br> and two 0x0 wrappers. Verified by diffing every rect before/after.
#
# ★★ …AND THEN A BLACK WEDGE (the FOURTH report, 2026-09-22: "this chamfer is revealing a black box
#   behind it. please chop this off.") — THE SAME RULE, APPLIED TOO HARD
#   Holding EVERY layer to the panel's edge left nothing behind the 14px cut but the page canvas,
#   --swu-bg #0b0f14. Measured on the owner's screenshot: the cut read rgb(12,15,20) against board
#   art at rgb(85,91,98). Invisible on a dark board, a black wedge on a light cosmetic one — and the
#   guard was green, because TestSchemaSetup's board happens to be near-black at that corner.
#   The panel FLOATS OVER the table, so the honest scene is that the table continues under it:
#     --swu-board-bleed-r: max(0px, calc(var(--swu-sidebar-w) - var(--pa-cut, 14px)));
#   consumed by the three ART layers only — .swu-board-bg, .swu-starfield, .swu-vignette. They bleed
#   exactly one chamfer's width under the panel; the cut then shows the table, continuous with the
#   board beside it, whatever art the player has chosen.
#   ⚠ THE FLAT SLABS STAY PUT. .theirStuffWrapper / .myStuffWrapper / #theirStuff / #myStuff are
#   featureless grey and it was THEIR overhang that made the cut read as uncut in the first place.
#   .swu-board-bg is opaque (it paints --swu-bg beneath the art) and sits above them at z 9, so the
#   14px strip is fully covered without them.
#   ⚠ max(0px, …) because the <800px breakpoint sets --swu-sidebar-w: 0 and a bare calc() would put a
#   fixed layer at right:-14px, hanging past the viewport.
#   ⚠ EXACTLY --pa-cut, not further. The glass blurs what is behind it; a wider bleed smears board art
#   into the panel's left edge as a band. Measured at 1430x1000 DPR2 over a WHITE board with the
#   vignette live: no seam (see the probe below).
#
#   ⚠⚠ THE TRAP, WHICH I SHIPPED FIRST: an opaque strip behind the panel painting the page colour.
#   It looked perfect — because the board happened to be dark. Set the background to WHITE and it is
#   instantly a black wedge. A painted cut cannot track its backdrop. Also wrong: clip/mask the
#   wrappers (clips the sidebar itself AND kills backdrop-filter), clip #theirStuff/#myStuff (clips
#   cards), repaint their backgrounds (misses the layer stacked on top).
#   ⚠ SAMPLE THE RIGHT PIXELS. A first "verified fixed" sampled y=0–4, ABOVE the board's top edge
#   (the board starts at y=4), read the dark body colour and looked green. Sample well inside the
#   14px cut triangle.
#   Full write-up: memory note chamfer-reveals-whats-behind-it.
#
# WHAT TO LOOK AT (desktop, 1700x1050)
#   • The sidebar is ONE panel: Undo/gear, Round + phase, Last Played, Game Log and the composer all
#     inside it. Frosted stone-grey, 1px steel hairline, soft drop shadow.
#   • ⚠ THE CHAMFER IS MIRRORED. Stock is top-left + bottom-right; this panel is flush to the RIGHT
#     edge of the screen, so a bottom-right cut would sit in the corner of the monitor where nobody
#     sees it. BOTH cuts are on the LEFT, facing the board, each with a sandy-gold corner glow.
#     Zoom both corners — they should be clean diagonal strokes, never a filled wedge.
#   • The panel must be DARKER than the board it borders, not lighter. This is the single easiest
#     thing to regress and the hardest to assert; look at a full-board screenshot, not a crop.
#   • Last Played is a sunken well (the seat-tile vocabulary). Section labels are Barlow, spaced
#     uppercase, ~52% white.
#   • The composer is a sunken well with a gold focus rim and a rounded gold Send — it was a flat
#     5%-white bar welded to a square button.
#     ⚠ Its look is owned by the `#chatWidget input#chatText` !important block near the bottom of
#     GameLayout.php. A second, more specific rule earlier in the file LOSES to it — edit that block,
#     do not add a competing one.
#   • Chat rows keep their seat-tinted rails inside the merged stream
#     (see GameLogAndChat_MergedSidebarStream.md — that merge is untouched).
#
# MOBILE IS DELIBERATELY UNCHANGED
#   GameLayoutMobile.php has its OWN #swuSidebar markup and CSS — an inline bottom drawer in page
#   flow (order:99, position:static), not an edge-mounted column — and does not inherit GameLayout's
#   styles. Verified 2026-09-22 at 390px: no .pa-glass class, no glass painted, its own
#   rgba(8,12,18,0.85) background, no horizontal scroll. So this change cannot regress mobile, and
#   mobile is the remaining style inconsistency if someone wants it matched later. A chamfered glass
#   panel there is a separate design decision (different shape, different corners).
#
# AUTOMATED PROBE (what was actually run)
#   DevTools/ui-harness/swusim-sidebar-hud-xbrowser.mjs, on a real board:
#     glass layer painted with a polygon clip · drop-shadow layer painted · panel itself unpainted so
#     the chamfer is not squared off · two corner glows wired · chamfer mirrored to the left ·
#     sidebar still full height at the right edge · composer is a sunken well (rgba(14,17,22,.42),
#     radius 4px) · the DARK per-surface --pa-glass override still in force · no page errors
#     ★★ THE CHAMFER MEASURED IN PIXELS, AT BOTH LEFT CORNERS. Screenshot -> data URL -> canvas in
#     the page, no image library. Three assertions, because each misses what the others catch:
#       1. CONTINUITY — every pixel strictly inside each cut triangle, against the board 4px left of
#          the panel AT ITS OWN ROW (the art is a gradient; one shared reference slackens it). This
#          is what a cut corner promises and what the black wedge broke.
#       2. GENUINELY CUT — .swu-board-bg, the layer actually behind the cut, is painted magenta and
#          the cut must follow it. Continuity alone would also pass for an opaque strip painted in
#          the board's colour, which is the fix that was shipped and then failed on a white board.
#       3. STRUCTURAL — the flat slabs must not cross the panel edge at all, and the three art layers
#          must reach EXACTLY --pa-cut past it. A pixel check only fires when the offending layer is
#          opaque and different at the sampled corner; .swu-starfield overhung 172px on the owner's
#          board and was invisible to one here.
#     ⚠ THREE WEAKER VERSIONS PASSED WHILE A BUG WAS PRESENT: "the cut differs from the panel" (the
#     in-panel sample landed on the gold UNDO button); "the cut matches document.body's background"
#     (the opaque strip was painting exactly that colour — it proved the colour, not the
#     transparency); and "the cut tracks the PAGE backdrop, body painted magenta" — which passed on
#     the black wedge, because the cut WAS tracking the page canvas and that was the bug.
#     Mutation-verified 2026-09-22, three mutants, each RED on the right assertion:
#       bleed reverted to the sidebar edge -> cut rgb(11,15,20) vs board rgb(0,3,8), off by 12, and
#         the probe shows cut #0b0f14 against a magenta board layer; art reach +0px
#       .swu-starfield back to inset:0      -> art reach .swu-starfield +200px
#       wrappers back to width:100%          -> flat slabs +200/+196px
#     Plus: below 800px (--swu-sidebar-w: 0, sidebar hidden) all three art layers must end exactly at
#     the viewport edge — the max(0px, …) guard, without which they hang 14px past it.
#   Chromium: 19/19 PASS   Firefox: 19/19 PASS   WebKit: 19/19 PASS   (2026-09-22)
#
#   ⚠ AND A REAL BOARD, because TestSchemaSetup's is near-black at that corner and hid this bug for a
#   whole round: DevTools/ui-harness/_cutprobe.mjs — a real Arenabot game at 1430x1000 DPR2 with
#   .swu-board-bg forced WHITE (harsher than the owner's cosmetic board, which measures rgb(85,91,98)
#   there). Cut vs board: off by 2 / 2 / 1 in Chromium / Firefox / WebKit, with the same probe run
#   against the pre-fix geometry for contrast — the wedge is plainly there and plainly gone.
#   ⚠ The fill's RESULT cannot be asserted: canvas cannot read composited backdrop-filter output. The
#     probe pins that the override is present; whether it looks right is the screenshot's job.
#
# NOT COVERED HERE
#   The mobile drawer · the merged log+chat stream itself (GameLogAndChat_MergedSidebarStream.md) ·
#   the waiting room and sideboard panels (ChatPanel_*.md) · the board, arenas and card rendering.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]

## WHEN

## EXPECT
TURNPLAYER:1
