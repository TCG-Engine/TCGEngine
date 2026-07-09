# SHD_079 Rival's Fall (6-cost event, Vigilance) — "Defeat a unit." Any unit is a valid target; with a
# friendly and an enemy unit present it's a real choice. P1 chooses the enemy SOR_128 → defeated; the
# friendly SEC_080 survives.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1
