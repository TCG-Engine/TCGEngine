# SOR_184 Fett's Firespray — When Played: if you control Boba Fett or Jango Fett, ready this unit.
# P1's leader IS Boba Fett (SOR_015) → Firespray (Space) enters READY instead of the default exhausted.

## GIVEN
P1LeaderBase: SOR_015/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY
