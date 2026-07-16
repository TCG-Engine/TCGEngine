# OnAttackDealGroundAndBase
#// LAW_184 Aerie (3/7, space) — On Attack: deal 2 damage to an enemy ground unit and 2 damage to a base.
#// Attacks the base: base takes 3 (combat) + 2 (ability) = 5; enemy SOR_046 takes 2.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_184:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:5
