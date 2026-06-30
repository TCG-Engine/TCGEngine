# SOR_075 It Binds All Things — "deal that much" equals the amount ACTUALLY healed. The heal amount is
# capped at the unit's damage: the chosen unit only has 2 damage, so the NUMBERCHOOSE max is 2; healing
# 2 (→ 0) makes the conditional deal 2, not 3. LAW_124 (4/7) takes DAMAGE:2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:2
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2
