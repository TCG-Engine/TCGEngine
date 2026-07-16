# OnAttackDealExhaust
#// LAW_076 Vult Skerris's Defender (3/3, space) — On Attack: you may deal 1 damage to a space unit and
#// exhaust it. Attacks the base; hit the enemy SOR_237 (1 damage + exhausted).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:EXHAUSTED
