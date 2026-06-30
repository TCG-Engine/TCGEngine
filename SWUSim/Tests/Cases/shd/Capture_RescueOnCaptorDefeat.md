# Capture rescue (CR 8.34.4): when the captor leaves play, all captives return to their owner's
# arena EXHAUSTED (no WhenPlayed triggers).
# Setup: P1 has SOR_095 (Battlefield Marine, 3/3) as the captor.
#        P2 has SOR_128 (Death Star Stormtrooper, 1/3) to be captured (index 0)
#             + LAW_124 (Industrious Team, 4/7) at index 1 — will defeat the captor via combat.
# Step 1: P1 plays SHD_131 Take Captive.
#   - Capturer: auto-picked (SOR_095, only P1 ground unit).
#   - Captive:  two P2 ground units → player picks SOR_128 (theirGroundArena-0).
#   - SOR_128 leaves P2's arena; becomes captive subcard on SOR_095.
# Step 2: playing the event passed the turn to P2 → P2's LAW_124 (now index 0) attacks P1's SOR_095 (captor).
#   - LAW_124 deals 4 damage to SOR_095 (3HP) → SOR_095 defeated.
#   - SWURescueCaptivesOf fires: SOR_128 returned to P2's ground arena EXHAUSTED (Status:0).
# Final: P1 has 0 ground units; P2 has LAW_124 (index 0, ready after attack is exhausted) +
#        rescued SOR_128 (index 1, exhausted per CR 8.34.4).
# Resources: 3 ready → 0 after paying SHD_131. (LAW_124 costs nothing to play; already in arena.)
# Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command covered; no penalty).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:1:CARDID:SOR_128
P2GROUNDARENAUNIT:1:EXHAUSTED
