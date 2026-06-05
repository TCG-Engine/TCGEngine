# SOR_011 Grand Inquisitor — Leader Action [Exhaust]: Deal 2 damage to a friendly unit with
# 3 or less power and ready it. The one eligible 3/3 friendly (exhausted) takes 2 damage and
# is readied.

## GIVEN
P1LeaderBase: SOR_011/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
