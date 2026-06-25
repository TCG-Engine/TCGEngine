# JTL_132 First Order Stormtrooper — On Attack: 1 indirect to a player. The trooper (power 2) attacks
# P2's base for 2 and deals 1 more indirect, totalling 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_132:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:3
