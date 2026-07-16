# FriendlyDefeatDealBase
#// LAW_056 Cassian Andor (4/4) — When a friendly unit's attack ends: if the defending unit was defeated,
#// deal 2 damage to a base. P1's SOR_046 attacks and kills SOR_128; Cassian deals 2 to P2's base.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2BASEDMG:2
