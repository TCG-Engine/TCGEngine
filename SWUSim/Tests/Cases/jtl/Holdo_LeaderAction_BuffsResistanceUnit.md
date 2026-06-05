# JTL_007 Admiral Holdo (leader) — Action [1 resource, Exhaust]: Give a Resistance unit (or a unit
# with a Resistance upgrade) +2/+2 for this phase. The only target is JTL_099 (Resistance, 2/1), which
# becomes 4/3.

## GIVEN
P1LeaderBase: JTL_007/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
