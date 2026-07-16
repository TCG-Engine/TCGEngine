# OnAttackDefeatCreditDeal
#// LAW_191 Arvel Skeen (4/3) — When Played/On Attack: you may defeat a Credit token (any player's). If
#// you do, deal 1 damage to a unit or base. Attacks the base; defeat P2's Credit -> deal 1 to P2's base
#// (base: 4 combat + 1 = 5).

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_191:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P2CREDITCOUNT:0
