# VISUAL CHECK — Inactivity kick vote, Twin Suns free-for-all (150s clock, 2 of 3 votes)
#
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/KickVote_TwinSuns.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat N: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=N&authKey=testschema
#   Stall seat 1:      .../zz_presence_poke.php?gameName=$GN&seat=1&back=400&actedOnly=1
#   DISCONNECT seat 1: .../zz_presence_poke.php?gameName=$GN&seat=1&back=400      (no actedOnly)
#   Automated: node DevTools/ui-harness/swusim-kick-vote-xbrowser.mjs
#
# WHAT TO LOOK AT
#   • All THREE other seats are prompted; the card shows a tally: "0 of 2 votes".
#   • One Yes is NOT enough: the voter's card switches to "You voted to kick — waiting for the others",
#     and the other seats' tally reads "1 of 2 votes".
#   • The second Yes removes seat 1: its board stops being rendered for everyone and the log reads
#     "P1 was removed for inactivity" then "Player 1 has been eliminated!".
#   • ★ NOBODY HEALS. Note each base's damage before the kick and confirm none of them drops. (A defeat
#     by an opponent heals 5 per CR §12.6.2; an administrative removal deliberately does not.)
#   • The table keeps playing — a surviving seat can still act (the removed seat's decision queue is
#     drained, or every "all queues" gate would soft-lock).
#   • DISCONNECT wording: with a true disconnect the card reads "P1 disconnected. Kick them?" instead.
#     A single poll from seat 1 closes it again.
#   • 3-seat game (kick one, then stall another): the threshold becomes UNANIMOUS (2 of 2).
#
# AUTOMATED PROBE (2026-09-17): covered by the same probe run — Chromium · Firefox · WebKit all PASS.
#   The no-heal rule is additionally pinned in process by
#   DevTools/tdd-regression/test_swusim_kick_removal_engine.php (base damage before/after).

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBaseDamage:9; theirBaseDamage:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
