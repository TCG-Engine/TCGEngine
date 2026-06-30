# SOR_075 It Binds All Things — without a friendly FORCE unit, only the heal happens; no damage may be
# dealt. P1 heals 3 from SOR_046 (damage 3 → 0); no deal decision is offered and the enemy is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
