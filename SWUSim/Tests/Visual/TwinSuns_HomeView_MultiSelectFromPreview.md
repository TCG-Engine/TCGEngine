# VISUAL CHECK — Twin Suns HOME view: an inline multi-select must be answerable from the preview tiles
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, stay on the HOME view, then play IG-2000 from P1's hand.
#
# WHY THIS EXISTS — bug #1020 (game 4158)
# "player tried to ping 3 units with ig-2000 but can't select all of them … the units highlight, but
# clicking them does not do anything to add to the unit count out of 3 for Confirm."
#
# JTL_140 IG-2000 is "When Played: Deal 1 damage to each of up to 3 units", i.e. ONE MZMULTICHOOSE over
# arena targets — so it takes the INLINE path (units glow in place, a draggable prompt bar carries the
# "n selected / 3 max" counter and Confirm), never the MZMultiChooseUI modal.
#
# ⚠ THE HOME VIEW IS THE ONLY PLACE MOST OF THESE TARGETS EXIST. body.swu-home hides every opponent
# zone, and swuBuildViews only puts opps[0] on-view (as `their…`) — seats past it are off-view entirely,
# so their units are rendered NOWHERE on the board, only as preview tiles. Preview tiles are therefore
# not a convenience here; they are the whole answer space for an "up to N" effect on the default view.
# swuPreviewTargetClick used to jump straight to SelectionMode.callback (the SINGLE-target submit) with
# no inline-multi branch — and an MZMULTI_INLINE decision never assigns a callback at all, so the click
# bailed on its first line and did literally nothing. A second defect sat behind it: the per-render
# validity filter in Core/UILibraries*.js dropped any pick whose zone had no window.<zone>Data binding,
# which is exactly every off-view seat-tagged pick.
#
# THE BOARD — a 4-seat Twin Suns game, P1 (you) to act, one unit per opponent plus one of your own,
# so the "up to 3" pool spans all four seats and NO auto-resolve is possible (4 targets > 3 picks).
#
# WHAT TO LOOK AT — play JTL_140 and stay on Home:
#   • Every one of the four units glows green as a legal target: yours on your own half of the board,
#     and P2/P3/P4's as `.mini-selectable` cards inside their preview tiles.
#   • The prompt bar reads "Deal 1 damage to each of up to 3 units" with a "0 selected / 3 max" counter.
#   • Click P3's unit IN ITS PREVIEW TILE. The counter must go to "1 selected / 3 max" and the mini card
#     must turn AMBER (.mini-selected) — matching the gold a picked unit takes on the main board. This
#     is the exact click that did nothing before the fix.
#   • Click P4's unit in its tile, then your own unit on the board: "3 selected / 3 max", Confirm
#     enabled. Picks made from a tile and picks made on the board must be interchangeable.
#   • With 3 picked, clicking P2's still-green mini card must do NOTHING — the cap holds on this path
#     too. (multiMax is enforced before the push.)
#   • Click an already-amber mini card again: it deselects, returns to green, counter drops to 2.
#     Revising a pick has to stay possible from the preview.
#   • ⚠ LET THE BOARD RE-RENDER while picks are held (the poll does this on its own — wait a few
#     seconds, or take any action that redraws). The counter must NOT drop back and the amber tiles must
#     NOT go green. This is the survival-filter half of the bug; it is invisible until a render tick.
#   • Confirm: exactly the three picked units take 1 damage each and the unpicked one takes none.
#     Check the damage lands on the RIGHT seats — a wrong remap would spray it across the wrong board.
#   • Confirm with nothing selected is legal ("up to" includes none) — no unit takes damage.
#   • Now Zoom In to a matchup view and repeat one pick from the real board, to confirm the on-board
#     path still behaves exactly as before (this fix must not have moved it).
#   • Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine,
#     so say so rather than implying it was covered.

## GIVEN
#// Twin Suns decks run TWO leaders sharing a force-side (all Villainy here).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myResources:6}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 6

#// One unit per seat — four targets for an "up to 3" pick, so nothing auto-resolves and the cap is
#// reachable. IG-2000 is a SPACE unit; the targets are deliberately mixed ground/space because the
#// ability says "units", not "space units".
WithP1GroundArena: [SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0]
WithP3SpaceArena:  [SOR_050:1:0]
WithP3Base: SOR_026:0
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4GroundArena: [SOR_108:1:0]
WithP4Base: SOR_026:0
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP1Hand: [JTL_140]

## WHEN

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P3SPACEARENACOUNT:1
P4GROUNDARENACOUNT:1
