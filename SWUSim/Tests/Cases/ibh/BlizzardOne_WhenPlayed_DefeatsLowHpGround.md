# IBH_099 Blizzard One (Ground, 5/7, Vigilance/Villainy, cost 7) — When Played: you may defeat a
#   non-leader ground unit with 3 or less remaining HP. A 3/1 enemy (1 remaining HP) is defeated; a 4/7
#   enemy (7 remaining) is NOT a valid target.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_099
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION
