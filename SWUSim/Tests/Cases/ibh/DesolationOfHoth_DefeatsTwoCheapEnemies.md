# IBH_104 The Desolation of Hoth (Event, cost 6, Vigilance) — Defeat up to 2 enemy units that each cost
#   3 or less. Two cheap enemies (cost 2 and 1) are eligible; a cost-8 body is NOT a target and survives.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION
