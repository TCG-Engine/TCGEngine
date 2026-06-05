# SOR_022 ECL: Obi-Wan Kenobi played out-of-aspect, paying printed cost + aspect penalty.
# SOR_049: cost 6, 4/6, Vigilance+Heroism. With SOR_014 (Aggression/Heroism) + SOR_022 (Command):
# Heroism covered, Vigilance uncovered → +2 penalty → player pays 8 total.
# Ambush attack into P2's ready BF Marine (3/3). Obi-Wan (4 power) kills Marine. Takes 3 back.
# Obi-Wan survives with 3 damage (6 HP). Resources exhausted to 0.

## GIVEN
SkipPreGame: true
P1LeaderBase: SOR_014/SOR_022
P2LeaderBase: SOR_014/SOR_023
WithP1Resources: 8:SOR_095
WithP1Hand: SOR_049
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_049
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED
