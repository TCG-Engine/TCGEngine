# SOR_188 Chopper (1/3) — "On Attack: Discard a card from the defending player's deck. If it's an
# event, exhaust a resource that player controls." Chopper (alone, no Raid) attacks the base; the
# milled card is an EVENT → exhaust one of P2's resources. Base takes Chopper's 1 power (no Raid).

## GIVEN
CommonSetup: yyw/rrk/{theirResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:0
