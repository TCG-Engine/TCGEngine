# JTL_003 Lando Calrissian (leader) — Action [1 resource, Exhaust]: Play a unit from your hand (paying
# its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit.
# P1 already controls a ground unit (SOR_095). It pays 1 (leader) + 2 (SOR_237 Alliance X-Wing, Heroism
# covered by Lando) = 3 → 0. Now controlling ground + space, it gives a Shield to the X-Wing.

## GIVEN
P1LeaderBase: JTL_003/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
