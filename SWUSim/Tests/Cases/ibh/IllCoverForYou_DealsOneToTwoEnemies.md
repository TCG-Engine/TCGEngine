# IBH_005 I'll Cover For You (Event, cost 3, Cunning) — Deal 1 damage to an enemy unit and 1 damage to
#   another enemy unit. Two enemy 3/3 bodies each take 1 (survive). First pick is chosen; the second
#   auto-resolves (only one "other" enemy remains).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION
