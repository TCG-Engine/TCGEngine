# SEC_046 Galen Erso — naming a card denies its own cost-reduction ability. SOR_248 Volunteer Soldier
# (cost 3) normally costs 1 less while you control a Trooper. P2 controls a Trooper (SEC_080), but after
# P1 names "Volunteer Soldier" the discount is gone, so P2 pays the full 3 (from 5 ready → 2 left, not 3).

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 5
WithP2Hand: SOR_248

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Volunteer Soldier
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2RESAVAILABLE:2
