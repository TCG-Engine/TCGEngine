# VISUAL CHECK — the ELIMINATED log line is RED and carries the phase-end warning
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#
# Owner, 2026-09-26: "add a red log when someone gets really eliminated that says '<player>
# eliminated! Game will end at the end of this phase'". A real elimination puts the whole game on a
# timer (CR 12.7.1 — the game ends at the end of the current phase and the highest remaining base HP
# wins). In game 1311538 the removal and the abrupt ending were three turns apart and nothing on
# screen connected them, so the ending read as a bug.
#
# SETUP — load in the Test Schema Editor (zzTestSchemaEditor.php), or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/Log_EliminatedRed.md" \
#        | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#
# WHAT TO LOOK AT — #swuLogPanel, the right-hand column.
#   1. The ELIMINATED row is RED (#e05050) and semibold. It must be the loudest line in the panel.
#   2. It reads "Player 2 has been eliminated! The game will end at the end of this phase — highest
#      base HP wins." — the warning is the point; the red alone does not say what is about to happen.
#   3. The ordinary rows around it (ATTACK / PLAY) stay the default grey. If everything is red the
#      rule is matching .swu-log-entry rather than .swu-log-ELIMINATED.
#   4. The CONCEDE pair below is NOT red and carries NO warning — a concession removes the seat but
#      does not end the game (owner ruling 2026-09-26), so promising that it will would be a lie, and
#      spending the alarm colour on it would blunt the one case that does mean "last phase".
#      "Player 4 has been eliminated!" must look like an ORDINARY row despite saying "eliminated" —
#      it is typed REMOVED, not ELIMINATED. This pair is the whole reason the check is not just
#      "is there a red line".
#
# ⚠ CHECK THE PHONE BOARD TOO — append &swuLayout=mobile at 430x932. GameLayoutMobile.php does NOT
#   load GameLayout.php's CSS; it is a second stylesheet with its own copy of the palette, and "the
#   log rules did not carry over" is the standing failure on this panel.
#
# ⚠ TWO SEATS ARE SHOWN AS GONE ON PURPOSE and they must not look alike: seat 2 was ELIMINATED (red +
#   warning) and seat 4 CONCEDED (plain). One screenshot with only the red line in it cannot tell a
#   correct build from one that reds every removal.
#
# Automated halves — neither is sufficient alone, keep both:
#   SWUSim/DevTools/tests/log_eliminated_colour_surfaces_test.php — the token and the rule exist on
#     BOTH stylesheets, the token is a real red (not aliased to the default grey), and the engine
#     writes the exact type string the selector spells. A source scan CANNOT see specificity.
#   DevTools/ui-harness/swusim-log-eliminated-xbrowser.mjs — COMPUTED colour, desktop + phone,
#     chromium + firefox + webkit.

## GIVEN
#// A four-seat Twin Suns board. Two leaders per seat — a Twin Suns visual test that shows one leader
#// per seat is testing the wrong layout (see the twinsuns-visual-tests note).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
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

#// ⚠ THE TEXT HERE IS COPIED FROM THE ENGINE, not paraphrased. SWUEliminateSeat composes exactly this
#// sentence; log_eliminated_colour_surfaces_test.php asserts the engine still writes it, so if the
#// wording is ever changed in one place the source scan goes red rather than this board drifting
#// quietly into describing a line the game no longer prints.
WithGameLog: ATTACK|ALL|P1's [[SOR_046|Rebel Trooper]] attacked P2's base for 3 damage
WithGameLog: ELIMINATED|ALL|Player 2 has been eliminated! The game will end at the end of this phase — highest base HP wins.
WithGameLog: PLAY|ALL|P3 played [[SEC_080|Imperial Dark Trooper]]
WithGameLog: CONCEDE|ALL|P4 conceded
#// ⚠ TYPE 'REMOVED', NOT 'ELIMINATED' — an administrative exit is neutral (owner, 2026-09-26: "only
#// genuine eliminations should be red to create the urgency of the end game"). The two removals must
#// look DIFFERENT on this board; that difference is the check.
WithGameLog: REMOVED|ALL|Player 4 has been eliminated!
