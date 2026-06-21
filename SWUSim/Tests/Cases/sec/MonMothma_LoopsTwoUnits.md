# SEC_103 Mon Mothma — the loop: "any number of other units, one at a time." P1 has two other units
#   (both SOR_046, 3/7) and P2 has two 1-HP enemies. Mon Mothma's first SOR_046 attacks one enemy; the
#   loop re-offers (the first attacker now excluded by UID); the second SOR_046 attacks the remaining
#   enemy (auto-targeted, last one); the loop re-offers, finds no eligible unit, and ends. Both enemies
#   are defeated; both attackers survive their 3 counter (7 HP). Proves the loop iterates and excludes
#   already-attacked units.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:3
