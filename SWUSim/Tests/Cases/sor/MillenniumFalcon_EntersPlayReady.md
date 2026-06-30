# SOR_193 Millennium Falcon "Piece of Junk" — "This unit enters play ready."
# Most units enter play exhausted; the Falcon enters READY. Played from hand for its cost (3),
# it lands in the Space arena ready to attack immediately.
# Han Solo (SOR_017, Cunning+Heroism) is the leader so the Falcon's aspects are fully paid for
# (cost stays 3, no off-aspect penalty).

## GIVEN
CommonSetup: gyw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_193
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:READY
P1RESCOUNT:3
P1RESAVAILABLE:0
