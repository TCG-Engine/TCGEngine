# TWI_160 Vanguard Droid Bomber — condition guard: with no OTHER Separatist unit in play (the bomber
# itself doesn't count), the When Played deals no base damage.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_160}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_160
P2BASEDMG:0
