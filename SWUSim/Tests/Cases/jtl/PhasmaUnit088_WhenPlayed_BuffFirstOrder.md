# JTL_088 Captain Phasma (unit) — When Played: You may give another First Order unit +2/+2 this phase.
# JTL_081 (a First Order unit, 2/1) becomes 4/3.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_088
WithP1Resources: 5
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
