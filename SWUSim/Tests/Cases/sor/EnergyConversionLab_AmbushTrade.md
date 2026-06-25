# SOR_022 Energy Conversion Lab: Epic Action plays BF Marine at printed cost, grants AMBUSH.
# P1 has exactly 2 resources (printed cost of SOR_095, no aspect penalty with SOR_014+SOR_022).
# Ambush attack into opponent's ready Marine: both 3/3 units trade. Both arenas empty.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 2:SOR_095
WithP1Hand: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED
