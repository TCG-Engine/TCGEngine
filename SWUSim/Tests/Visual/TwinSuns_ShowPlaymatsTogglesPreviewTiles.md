# VISUAL CHECK — Twin Suns: "Show playmats" governs the home PREVIEW TILES, not just the board halves
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   GN=$(curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   ⚠ authKey=testschema is REQUIRED here and is the whole point of the fixture: SWUCosmeticSeatOverrides()
#     forces a DISTINCT playmat per seat in any >2-seat test game (1=lof / 2=jtl / 3=law / 4=shd key art).
#     Without it every seat resolves to an empty playmat, all four tiles are bare, and the check is
#     vacuous — it passes against a completely broken toggle.
#
# THE BUG THIS PINS (reported 2026-08-20, two symptoms, one root cause)
#   "the Show Playmats checkbox is not being respected on this Twin Suns view. sometimes all the
#    playmats show and sometimes one shows"
#   swuRenderHomeStrips() used to emit the tile's keyart as an INLINE style while building the tiles'
#   innerHTML, making it a SECOND writer of the same visual concept. ApplyCosmeticPlaymats() — the only
#   function that reads the ShowPlaymats setting — queries .swu-playmat-top / .swu-playmat-bot / the two
#   mobile arena rows, and NEVER .swu-home-strip. So:
#     • the toggle could not affect the tiles at all (it had no code path to them), and
#     • the two renderers fire on DIFFERENT signals — tiles on board-data change, cosmetics on the 6s
#       CosmeticsLive poller + a MutationObserver — so tile art could lag or disagree with the rest of
#       the board until some unrelated board mutation happened to re-render the strips. That divergence
#       is the "sometimes" in the report.
#   Fix: ONE reader (swuShowPlaymats()) and ONE painter (swuPaintHomeStripPlaymats(), which mutates
#   style in place — never innerHTML), called from BOTH swuRenderHomeStrips() (after the rebuild) and
#   ApplyCosmeticPlaymats().
#
# WHAT TO LOOK AT (4 seats, opened as playerID=1, so P2/P3/P4 get preview tiles)
#   • Gear menu → "Show playmats" CHECKED: all three tiles carry their seat's keyart behind a dark
#     tint, and the board's own two halves carry theirs. Every tile is DIFFERENT art — that is what
#     proves the paint is per-seat (data-seat) rather than one shared image.
#   • UNCHECK it: all three tiles lose the keyart and instead carry a WHOLE-TILE WASH — the same
#     rgba(0,0,0,0.35) the mini-board arena boxes use, expanded from just those boxes to the entire
#     tile (.swu-home-strip:not(.has-playmat)). The board halves lose their mats too.
#     ⚠ Check this over a BRIGHT board cosmetic, not the default starfield — that is the only state
#     where it matters. .swu-home-strip's own background is --swu-surface = rgba(255,255,255,0.04), an
#     almost-transparent WHITE wash: over a dark board it passes for a panel, but over bright keyart the
#     header row, the chips and the gaps let the board through at full brightness while the arena boxes
#     stayed scrimmed, so the tile read as half-shadowed. (Repro without a cosmetic: in the console set
#     document.querySelector('.swu-board-bg').style.filter = 'brightness(3.2)'.)
#     ⚠ The fill is UNIFORM: .swu-mb-arena drops its own 0.35 in the no-keyart state, so the tile wash
#     IS the arena background and SPACE/GROUND are marked out by their BORDERS alone. If the boxes ever
#     read as darker rectangles again, that scoping has been lost and the tile is two-tone.
#     ⚠ WITH a playmat the boxes KEEP their 0.35 over the 0.72 art tint — the established look, and the
#     reason the rule is scoped to :not(.has-playmat) rather than applied unconditionally.
#     ⚠ Desktop only, by construction — mobile renders .swu-seat-row, not .swu-home-strip, and its mat
#     already rides ApplyCosmeticPlaymats' paintRow().
#
# ARENA BORDER COLOURS (both toggle states)
#   • SPACE  = silver       rgba(202,212,224,0.70)   .swu-mb-arena--space
#   • GROUND = sandy brown  rgba(198,160, 99,0.70)   .swu-mb-arena--ground
#   The two boxes previously carried IDENTICAL classes and differed only by their text tag, so nothing
#   could style them apart — the --space/--ground modifiers were added for this.
#   ⚠ On a playmat-less tile the fill is uniform, so the border is the only thing separating the two
#   arenas at preview size: the colour carries real information there, not decoration.
#   ⚠ The "SPACE"/"GROUND" text tags were REMOVED from these tiles (they cost a line of height per box
#   at preview size and repeated what the layout already says). The non-colour channel is now the
#   arenas' fixed vertical ORDER — space above ground, exactly as on the full board — so a colourblind
#   viewer still reads them correctly and the borders only reinforce it. ⚠ That makes the ORDER
#   load-bearing: if the two boxes are ever reordered or made conditional, the tags have to come back.
#   (The tags were desktop-tile-only — swuRenderMiniBoard is called from the non-mobile branch alone —
#   so .swu-mb-atag now has no emitter and its CSS rule was deleted with them.)
#   ⚠ Check the sandy GROUND border against an ACTIVE-TURN tile: the amber ring (240,192,64) is a
#   saturated GLOW on the tile's outer edge, the sand is a muted flat line on an inner box. Rendered
#   side by side at 2x and they do not compete — but if the sand is ever brightened, re-check this.
#   • RE-CHECK it: the art comes straight back, with no board action in between. Before the fix the
#     tiles only ever changed on a board re-render, so an immediate flip did nothing to them.
#   • ★ THE REGRESSION CASE — with the toggle OFF, force a strip rebuild (take an action, or in the
#     console `swuRenderHomeStrips()`), and the tiles must STAY bare. The old inline-style version
#     repainted the art on every rebuild, so the setting appeared to "come undone" by itself.
#
# AUTOMATED PROBE (what was actually run — the assertion is the computed style, not a screenshot)
#   Eight computed-style assertions per tile, across ON / OFF / OFF-after-swuRenderHomeStrips():
#     toggle OFF clears art · OFF applies the whole-tile wash · OFF arena fill is transparent ·
#     OFF survives a re-render · toggle ON restores art · ON keeps arena fill 0.35 ·
#     SPACE border silver (both states) · GROUND border sandy brown (both states) ·
#     no SPACE/GROUND text tags remain.
#     Chromium: ALL PASS      Firefox: ALL PASS
#   Reverting GameLayoutShared.php to the pre-fix version and re-running the SAME probe gives
#   OFF=ART,ART,ART — i.e. the probe is discriminating, not merely green.
#   ⚠ WebKit could NOT be verified on this machine: playwright's webkit LAUNCHES fine, but the first
#     newPage()/about:blank never completes (>90s). So Safari is UNVERIFIED for this change. The fix is
#     plain backgroundImage set/clear with no engine-specific construct, so the risk is low — but it is
#     unverified, not verified.
#
# NOT COVERED HERE
#   The MOBILE Twin Suns rows (swuRenderSeatRow) carry no playmat at all — the mobile mat backs the
#   arena rows via ApplyCosmeticPlaymats' paintRow(), which already honoured the toggle. If mobile rows
#   ever gain keyart, they must go through swuPaintHomeStripPlaymats(), not a second inline style.

## GIVEN
#// Twin Suns needs TWO leaders per seat sharing a force-side, and a base per seat — a one-leader board
#// is not the format and hides per-leader render bugs.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 3
WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP1SpaceArena: [SOR_031:1:0 SOR_040:1:3]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]
WithP2SpaceArena: [SOR_050:1:0 SOR_052:1:3]
WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2]
WithP3SpaceArena: [SOR_060:1:0 SOR_066:1:3]
WithP4GroundArena: [SOR_038:1:0 SOR_039:1:2]
WithP4SpaceArena: [SOR_041:1:0 SOR_042:1:3]

## WHEN

## EXPECT
SEATCOUNT:4
