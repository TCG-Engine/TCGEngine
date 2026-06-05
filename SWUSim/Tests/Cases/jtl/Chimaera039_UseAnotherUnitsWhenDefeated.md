# JTL_039 Chimaera — When Played: may use a "When Defeated" ability on another friendly unit.
# P1 plays Chimaera; chooses JTL_087 (alive, "When Defeated: create a TIE") to use its ability.
# JTL_087 stays in play; a TIE token is created. Arena ends with JTL_087 + Chimaera + TIE = 3.

## GIVEN
P1LeaderBase: JTL_005/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3
