# SOR_012 IG-88 — when you do NOT control more units than the defending player, no +1/+0.
# P1 controls 1 unit, P2 controls 1 unit (equal) → no bonus. The 3-power unit attacks the base
# (chosen over the enemy unit) for 3 damage.

## GIVEN
P1LeaderBase: SOR_012/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
