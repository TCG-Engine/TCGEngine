# SOR_018 Jyn Erso — Leader Action [Exhaust]: Attack with a unit. The defender gets -1/-0
# for this attack. P1's 3/3 attacks P2's 3/7; the defender's power is reduced 3 → 2, so the
# attacker takes only 2 counter-damage (3 without the debuff). The defender takes the full 3.

## GIVEN
P1LeaderBase: SOR_018/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED
