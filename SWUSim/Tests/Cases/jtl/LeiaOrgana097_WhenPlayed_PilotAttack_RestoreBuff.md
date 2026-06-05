# JTL_097 Leia Organa — When Played: you may attack with a Pilot unit; it gets +1/+0 and Restore 1 for
# this attack. The ready Pilot JTL_046 (power 3) attacks the enemy base for 3+1=4, and Restore 1 heals
# P1's base from 3 damage to 2.

## GIVEN
P1LeaderBase: JTL_001/SOR_020:3
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_097
WithP1Resources: 7
WithP1GroundArena: JTL_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1BASEDMG:2
