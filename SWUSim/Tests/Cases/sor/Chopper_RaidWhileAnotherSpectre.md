# SOR_188 Chopper — "While you control another SPECTRE unit, this unit gains Raid 1." With Kanan
# (another Spectre) in play, Chopper attacks the base for 1+1(Raid)=2. (The milled top card is a
# unit, so no resource is exhausted.)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP1GroundArena: SOR_047:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P2DECKCOUNT:0
