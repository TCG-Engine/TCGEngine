# VISUAL CHECK — the effect stack's trigger-ordering row: Plot icon tile + timing-window labels
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then deploy P2's leader as a Pilot.
#
# WHY THIS EXISTS — bug #1024 (game 4161), and the two UI defects found while fixing it.
# The report was a RULES bug: "Deploying Boba JTL does not let me choose order of Plot Cinta Kaz and
# Boba's When attached as Pilot trigger." That is fixed server-side (CR 19.a makes Plot a triggered
# ability, CR 7.6.9 gives the player the order) and is covered by Tests/Cases/keywords/Plot_TriggerOrdering.md.
# This file covers what the fix made VISIBLE, which no schema test can see because it is render text:
#
#   1. THE BADGE LEAKED DATABASE KEYS. The label map covered 13 of 112 trigger types and fell back to
#      `|| sharedCardData.TriggerType`, so the first screenshot of the new ordering row read
#      "SWU_PLOT_WIND…" and "WHENPLAYEDASUPGRADE" — overlapping each other. ~95 per-card reaction types
#      (SHD_133, LOF_229, …) had been leaking the same way for as long as the effect stack has existed.
#   2. THE PLOT TILE SHOWED A CARD IT DOES NOT MEAN. The window's EffectStack entry carries a CardID
#      only so the tile can render, and that CardID is the FIRST AFFORDABLE Plot card in resources — so
#      with two Plot cards the tile showed one card's art while clicking it opened a window offering
#      both. The art promised a card; the click delivered a window.
#
# ⚠ ONLY A HUMAN CAN CHECK THIS. The schema suite is server-side and never renders; the render suite
# does not build a two-trigger effect stack. `DevTools/tests/effect_stack_labels_test.php` guards that
# every trigger type HAS a label and that the raw-id fallback stays deleted, but it cannot see whether
# the row LOOKS right — the icon, the fit, the overlap.
#
# THE BOARD — game 4161's, trimmed. P2 holds JTL_009 Boba Fett (Epic Action: deploy as a Pilot on a
# friendly Vehicle) with SEC_171 Punishing One in space to carry him, and TWO Plot cards in resources:
# SEC_172 Cinta Kaz (cost 6) and SEC_088 First Light (cost 7). Two Plot cards is the point — one card
# cannot show the "which card is that tile?" defect at all.
#
# ── WHAT TO DO ────────────────────────────────────────────────────────────────────────────────────
#   1. As P2, click the leader → "Deploy as Unit or Pilot?" → choose PILOT.
#   2. The trigger-ordering row appears: the EFFECT STACK panel with TWO tiles.
#
# ── WHAT TO LOOK AT ───────────────────────────────────────────────────────────────────────────────
#
# THE PLOT TILE (left):
#   • It shows the ANIMATED PLOT ICON (Assets/Icons/plot.webp — the same one the HasPlot resource
#     counter uses), centred on a dark tile. It must NOT show Cinta Kaz's or First Light's card art.
#   • ⚠ THE CONTAINER IS THE SAME SQUARE as the Boba tile beside it — same width, same height, same
#     corner radius, sitting on the same baseline. The icon fills the tile; it does not resize it.
#     If the two tiles are different sizes, `inset: 0` is not resolving against the card box.
#   • THE ICON GOES EDGE TO EDGE. The badge is a circle inscribed in a square canvas, so at 100% with
#     object-fit:contain its rim should touch the tile's sides with no field of empty background around
#     it. A small icon floating in the middle means the img sizing was reverted (a first pass sat at
#     58% and read as an afterthought). It must not be CROPPED either — the tile is only approximately
#     square, and `cover` would clip the rim on the shorter axis.
#   • The icon animates (it is an animated .webp). A frozen first frame means the asset was re-encoded
#     as a still somewhere in the pipeline.
#   • HOVER IT. Nothing must pop up. The card art is still rendered UNDERNEATH the cover, and if the
#     hover reaches it you get Cinta Kaz's detail popup — which leaks exactly the card this tile exists
#     to stop implying. (`pointer-events: auto` on .swu-es-plot-tile is what swallows the hover.)
#   • CLICK IT. It must still be pickable — the click has to bubble past the cover to the tile's own
#     handler. This is the pair to the hover check and the two pull in opposite directions, which is
#     why both are listed.
#
# THE LABELS:
#   • The Plot tile reads "PLOT". The Boba tile reads "WHEN PLAYED".
#   • NEITHER may contain an underscore or an ALL-CAPS code. "SWU_PLOT_WINDOW" or "WHENPLAYEDASUPGRADE"
#     means the map lookup missed and something restored the raw-id fallback.
#   • The two pills must not touch or overlap each other.
#     ⚠ KNOWN AND DELIBERATELY NOT FIXED YET: the badge is `white-space: nowrap` centred on a ~110px
#     tile, so a label longer than the tile still overflows both edges. "PLOT" and "WHEN PLAYED" are
#     short enough to be clean here. The overflow is real for the longest labels in the set — "Unit
#     Played or Created" (ASH_017) and "Attacks Costlier Unit" (HMW_014) — and both are LONGER than the
#     raw ids they replaced, so those two look worse than before until the wrap fix lands. Do not file
#     that as a regression of this change; it is a separate queued item.
#
# ORDER, so the tiles are not ambiguous:
#   • The Plot tile is EffectStack-0 and Boba is EffectStack-1 — the window is armed in SWUDeployLeader
#     before the Unit/Pilot branch stages the leader's own trigger. Left-to-right should match.
#   • Pick the PLOT tile: the Plot offer opens over the RESOURCES and now lists BOTH Cinta Kaz and
#     First Light. That is the confirmation that one tile really did stand for the whole window.
#   • Re-run and pick BOBA first instead: his "up to 4 damage" split comes up, and the Plot window
#     opens after it. Both orders must be reachable — that is the rules fix itself.
#
# ── CROSS-BROWSER ─────────────────────────────────────────────────────────────────────────────────
# ⚠ Per the project rule, check Chromium, Firefox AND Safari. The two things at risk here are engine-
# divergent: `inset: 0` on an absolutely-positioned child resolving against the card box, and animated
# .webp playback (Safari historically the odd one out). A still icon in Safari with motion elsewhere is
# an asset problem, not a CSS one.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:HMW_011;
  myBase:JTL_024;
  theirLeader:JTL_009;
  theirBase:JTL_031
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
#// TWO Plot cards, both affordable, so the single tile provably stands for a multi-card window.
WithP2Resources: 1:SEC_172:1,1:SEC_088:1,8:SOR_046:1
#// The Vehicle Boba attaches to as a Pilot — without it the deploy never offers the Pilot branch.
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]

## WHEN
#// Driven by hand in the editor — see "WHAT TO DO" above.

## EXPECT
#// Visual only; the behavioural assertions live in Tests/Cases/keywords/Plot_TriggerOrdering.md.
