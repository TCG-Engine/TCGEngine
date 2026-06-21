# LOF_134 Heavy Missile Gunship — Action [Exhaust]: deal 2 damage to a ground unit. It exhausts and deals
# 2 to the enemy 3/7.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_134:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:EXHAUSTED
