# VISUAL CHECK — Twin Suns: an inline multi-select keeps its picks while you page between matchup views
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Scripted in Chromium, Firefox and WebKit,
# phone and desktop, by DevTools/ui-harness/swusim-multiselect-across-views-xbrowser.mjs.
#
# WHY THIS EXISTS — reported 2026-10-27 on mobile (LAW_002 Tobias Beckett's deploy, "and also this involves any time I
# need to target multiple units"): "when I change pages to look at other opponents it deselects what I selected".
# On a phone the Home rows draw no unit thumbnails, so a pick is made on the real board of a matchup view (P1 vs P2,
# P1 vs P3, …). A board pick was stored as the RENDERED id — `theirGroundArena-0` — and "their" is a different seat
# on every matchup view, so paging either dropped the pick (that seat has no unit there) or moved it onto the next
# opponent's unit, and Confirm then submitted THAT unit.
#
# THE BOARD — the same 4-seat board as TwinSuns_HomeView_MultiSelectFromPreview.md: one unit per seat, four targets
# for JTL_140 IG-2000's "Deal 1 damage to each of up to 3 units". P2's and P4's units are both at
# `theirGroundArena-0` on their own matchup views — the collision the old storage could not tell apart.
#
# WHAT TO LOOK AT — play IG-2000, then (phone: ▶ / ◀ arrows; desktop: the same):
#   • Zoom In to P1 vs P2 and tap P2's unit: "1 selected / 3 max", the unit turns gold.
#   • Page to P1 vs P3: the counter still reads 1. Tap P3's space unit: 2.
#   • Page to P1 vs P4: P4's unit must be GREEN (selectable), NOT gold — the P2 pick must not have moved onto it.
#     The counter still reads 2. Tap it: 3.
#   • Page back to P1 vs P2: P2's unit is still gold, counter 3.
#   • Confirm: exactly P2's, P3's and P4's units take 1 damage; your own unit takes none.

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
