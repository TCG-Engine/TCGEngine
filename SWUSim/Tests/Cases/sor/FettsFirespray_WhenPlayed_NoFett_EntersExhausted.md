# SOR_184 Fett's Firespray — without a Boba/Jango Fett you control, the WhenPlayed does nothing and
# Firespray enters EXHAUSTED (CR default). Thrawn (SOR_016, Cunning/Villainy) covers the cost but is
# not Boba/Jango.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:EXHAUSTED
