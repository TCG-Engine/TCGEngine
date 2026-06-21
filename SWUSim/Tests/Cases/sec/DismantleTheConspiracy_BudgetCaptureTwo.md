# SEC_106 Dismantle the Conspiracy (Event, Command/Heroism, cost 6) — a friendly unit captures any
#   number of enemy non-leader units with a total of 7 or less remaining HP. SOR_095 captures both
#   1-HP SOR_128s (total 2 ≤ 7).

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_106

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION
