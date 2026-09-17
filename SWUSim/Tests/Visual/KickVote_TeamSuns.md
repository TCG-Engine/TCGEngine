# VISUAL CHECK — Inactivity kick vote, Team Suns 2v2 (150s clock, both opponents must agree)
#
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/KickVote_TeamSuns.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat N: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=N&authKey=testschema
#   Stall seat 1 (RED): .../zz_presence_poke.php?gameName=$GN&seat=1&back=400&actedOnly=1
#   Automated: node DevTools/ui-harness/swusim-kick-vote-xbrowser.mjs
#
# TEAMS ARE SEAT PARITY: 1+3 = RED, 2+4 = BLUE.
#
# WHAT TO LOOK AT
#   • Only the OPPOSING team (seats 2 and 4) is prompted, and the tally needs BOTH: "0 of 2 votes".
#   • ★ Seat 3 — the target's own TEAMMATE — is never prompted and cannot vote, because a kick hands
#     the other team the win. (The server refuses a hand-built vote from seat 3 too.)
#   • One blue Yes is not enough; the second ends the game and BLUE WINS: the end-game overlay shows the
#     shared victory for seats 2+4, and the log reads "P1 was removed for inactivity".
#   • Remove the WithP1GlobalEffect line below to A/B against the free-for-all rules (then all three
#     others are prompted and only 2 of 3 are needed).
#
# AUTOMATED PROBE (2026-09-17): covered by the same probe run — Chromium · Firefox · WebKit all PASS.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
#// ⚠ THIS LINE IS WHAT MAKES IT TEAM SUNS.
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
