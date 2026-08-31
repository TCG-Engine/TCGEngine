# VISUAL CHECK — Twin Suns HOME view: split damage must be assignable to EVERY seat, from the tiles
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, stay on the HOME view, then play Ninth Sister from P1's
# hand. Companion to TwinSuns_HomeView_MultiSelectFromPreview.md — same family, different decision UI.
#
# WHY THIS EXISTS — bug #1022 (game 4158)
# "player can't spread damage from ninth sister's effect, won't let them select any units other than
# their own for damage."
#
# ASH_148 Ninth Sister is "When Played: An opponent discards a card from their hand. You may deal
# damage equal to its cost divided as you choose among any number of units" — an MZSPLITASSIGN whose
# pool is SWUAllUnits(), i.e. every unit on the table.
#
# ⚠ THE mzIDs AND THE DOM ids ARE DIFFERENT ALPHABETS. Once a game has more than two seats ZoneSearch
# hands back SEAT-TAGGED ids (`p3GroundArena-1`) for every opponent, while your own units stay `my…`.
# No renderer ever emits a `p{n}` DOM id — only `my…`/`their…`, and only for the two seats the current
# view draws. MZSplitAssignUI attached its −/+ overlay with a bare document.getElementById, so it
# resolved the caster's own units and NOTHING else. That was broken in EVERY Twin Suns view, not just
# Home, and for Team Suns teammates too. The engine now resolves through an app-supplied hook
# (window.MZSplitResolveTargetElement); SWUSim tries own id → the rendered frame for this view → the
# preview tile, and treats a `display:none` card as unusable so the Home view falls through to the tile.
#
# THE BOARD — 4 seats, P1 (you) to act with 7 resources. One unit per opponent plus one of your own, so
# a split has to reach three different seats to be spent. Every opponent holds exactly ONE card, and
# all three are cost 5, so whichever opponent you pick the discard auto-resolves and the pool is 5.
#
# WHAT TO LOOK AT — play ASH_148, pick an opponent, then work the split:
#   1. The opponent picker appears (three eligible seats, all holding a card). Pick any one.
#   2. That seat discards its card; the banner reads "Divide up to 5 damage among any number of units"
#      with "Remaining: 5" and a disabled-until-legal Confirm.
#   3. ⚠ THE CORE CHECK: a −/+ overlay must appear on FOUR units — your own on your half of the board,
#      and P2/P3/P4's inside their preview tiles. Before the fix only your own had one. A tile whose
#      unit has no overlay is the bug, back.
#   4. The overlay must FIT its tile card: buttons and the readout scale off --swu-mb-unit, so at every
#      viewport the media query can produce they must sit inside the card, not overhang it.
#   5. Assign 2 to a unit in one tile and 3 to a unit in another. "Remaining" counts down to 0, the
#      assigned tiles show their amounts, and Confirm enables.
#   6. ⚠ LET THE BOARD RE-RENDER while amounts are held (wait for the poll, or take any action that
#      redraws). The tiles are rebuilt with innerHTML on every board update, which takes the overlays
#      with them — swuRenderHomeStrips re-injects. The −/+ controls and their amounts must both
#      survive. This is the half of the bug that is invisible until a render tick.
#   7. Confirm: the damage lands on the units you assigned it to, on the RIGHT seats. Check each
#      opponent's tile damage counter — a bad remap would spray it onto the wrong board.
#   8. "You may" makes it declinable: Confirm with 0 assigned deals no damage anywhere.
#   9. Now Zoom In to a matchup view and repeat: that opponent's units are drawn on the real board, so
#      their overlays must be on the CARDS there, not on tiles — the resolver prefers the visible
#      rendering. Your own units behave identically in both views.
#  10. Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine,
#      so say so rather than implying it was covered.

## GIVEN
#// Twin Suns decks run TWO leaders sharing a force-side (all Villainy here).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 7

#// One unit per seat — a 5-point split cannot be spent without reaching other seats' boards.
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

#// Every opponent holds exactly ONE card and all three are the SAME cost-5 card (SOR_078 Vanquish), so the
#// "an opponent" picker still appears (three eligible) while the discard auto-resolves and the split
#// pool is 5 whichever seat is chosen. A mixed-cost hand would make the pool depend on the pick and
#// the check ambiguous. An EVENT is used deliberately: only its printed cost matters here, and an event
#// in hand cannot be confused with the units the split is being assigned to.
WithP2Hand: [SOR_078]
WithP3Hand: [SOR_078]
WithP4Hand: [SOR_078]
WithP1Hand: [ASH_148]

## WHEN

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P3SPACEARENACOUNT:1
P4GROUNDARENACOUNT:1
P2HANDCOUNT:1
P3HANDCOUNT:1
P4HANDCOUNT:1
