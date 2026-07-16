# UseForce_Exhaust
#// LOF_178 Adept of Anger — Action [Exhaust, use the Force]: exhaust a unit. P1 exhausts the Adept, uses
#// the Force, and exhausts the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_178:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED
