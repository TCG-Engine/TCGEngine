# SHD_041 Kuiil — negative aspect case. Base is SOR_020 (Vigilance). The milled top card SOR_095
# (Command,Heroism) shares NO aspect with the base → it stays in the discard pile (not returned to hand).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_041:1:0
WithP1Deck: [SOR_095 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DECKCOUNT:1
P1DECKTOPCARD:SEC_080
