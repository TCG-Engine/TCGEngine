# SOR_009 Leia Organa — Leader Action [Exhaust]: Attack with a Rebel unit. Then, you may attack
# with another Rebel unit. P1 has two Rebels; both attack the base (opponent has only a base) for
# 3 each → 6 total.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1LEADER:EXHAUSTED
