# WhenDefeatedDealBase
#// LAW_189 Cavern Angels X-Wing (2/1, space) — When Defeated: deal 2 damage to a base. Attacks SOR_237
#// (2/3) and dies to the counter; deal 2 to P2's base.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_189:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
