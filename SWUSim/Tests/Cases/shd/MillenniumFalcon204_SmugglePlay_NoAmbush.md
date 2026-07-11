# SHD_204 Millennium Falcon smuggled from resources — NOT played from hand, so NO Ambush: she
# enters exhausted with no entry attack (no decision), enemy TIE untouched.

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SHD_204:1
WithP1Deck: SOR_095
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION
