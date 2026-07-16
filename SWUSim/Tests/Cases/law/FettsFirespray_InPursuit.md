# CreditOnDefenderDefeated
#// LAW_252 Fett's Firespray (4/6 space, Ambush) — "When Attack Ends: If the defending unit was defeated,
#// create a Credit token." Firespray attacks SOR_237 (2/3 space): deals 4 → SOR_237 defeated; takes 2 →
#// Firespray (6 HP) survives. Defender defeated → 1 Credit created for P1.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_252:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1CREDITCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_252
