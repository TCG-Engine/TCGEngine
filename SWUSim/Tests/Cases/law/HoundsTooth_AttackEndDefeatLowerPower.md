# LAW_033 Hound's Tooth (8/8, space) — When Attack Ends: if this unit survived, you may defeat a unit
# with less power than this unit. Attacks the base and survives; defeat the enemy SOR_046 (power 3 < 8).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
