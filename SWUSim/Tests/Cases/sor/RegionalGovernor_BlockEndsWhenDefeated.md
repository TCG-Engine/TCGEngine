# SOR_062 Regional Governor — "While THIS UNIT is in play …". The block ends when Governor leaves
# play. P1 plays Governor and names "Battlefield Marine". P2 attacks Governor (1/4) with SOR_210
# (4/3) and defeats it. P1 passes. Now P2 can play their Battlefield Marine (SOR_095) — the block is
# gone because Governor is no longer in play.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095
WithP2GroundArena: SOR_210:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2HANDCOUNT:0
