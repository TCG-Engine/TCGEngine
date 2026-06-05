# SHD_187 Lurking TIE Phantom — "This unit can't be captured, damaged, or defeated by enemy card
# abilities." P1 plays Open Fire (SOR_172: deal 4 damage to a unit) at the Phantom; the damage is
# prevented (it stays at 0 damage).

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:SOR_172}
P1OnlyActions: true
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
