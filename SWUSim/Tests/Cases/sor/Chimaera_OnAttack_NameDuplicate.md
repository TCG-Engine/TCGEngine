# SOR_185 Chimaera — the text discards "A card with that name" (one copy), not all copies. P2's
# hand is two Death Star Stormtroopers (SOR_128). P1 names "Death Star Stormtrooper"; exactly ONE
# copy is discarded (hand 2 → 1, discard 1).

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_128
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Death Star Stormtrooper
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128
