# VISUAL CHECK — TWI_017 "Flipatine" shows its VILLAINY face once flipped
#
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/FlipatineLeaderFace.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat 1: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema
#   Seat 2 (the opponent — the flip is PUBLIC): same URL with playerID=2
#   A/B: delete the `myLeaderFlipped:true` opt below to see the Heroism face in the same slot.
#
# WHAT THIS PINS (bug report, game 506505, 2026-09-17)
#   "I used Chancellor Palpatine's ability and he exhausted, but didn't flip to Villainy side… I do get
#    to heal and draw, but I don't get the Villainy side."
#   The ENGINE was always right (Deployed=true = flipped, with the draw + heal applied). The POLL PAYLOAD
#   was wrong: the leader slot shipped the FRONT CardID, so the client drew the Heroism face forever.
#   SWULeaderDisplayCardID() (GameLogic.php) had handled this for ages, but zzGameCodeGenerator.php only
#   wired it into the DisplayMode=="Single" branch while SWUSim's Leader zone is `Mode=All` — a helper
#   with NO CALLER. Payload side pinned by DevTools/tdd-regression/test_swusim_flipatine_leader_art.php.
#
# WHAT TO LOOK AT
#   • The leader slot reads "DARTH SIDIOUS — Playing Both Sides" (Separatist · Sith), NOT "Chancellor
#     Palpatine". That is the same card's flipped face; the art file is TWI_017_back.webp.
#   • Its text is the VILLAINY action: "If you played a Villainy card this phase, create a Clone Trooper
#     token, deal 2 damage to each enemy base, then flip this leader."
#   • ⚠ Flipatine has NO arena unit — the flip happens IN PLACE in the leader slot. The slot must NOT be
#     ghosted/dimmed the way a deployed leader's slot is (three client seams special-case TWI_017 for
#     exactly this: swuRenderMiniBoard, applyLeaderDeployedClass, and the mini-board tile).
#   • The opponent's view of the same seat shows the same Villainy face.
#   • The Twin Suns mini-board tile (top strip in a 3-4 seat game) also shows the flipped face.
#
# AUTOMATED PROBE (2026-09-17): payload asserted for own view / opponent / spectator, plus an unflipped
#   control, by test_swusim_flipatine_leader_art.php (8 checks, mutation-verified by reverting the
#   generator arm). Chromium screenshot of the real reported game (506505, seat 3) read by hand: the slot
#   renders Darth Sidious with the Villainy text at TWI_017_back.webp.

## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:4}
P1OnlyActions: true
WithP1GroundArena: [SOR_032:1:0]

## WHEN

## EXPECT
TURNPLAYER:1
