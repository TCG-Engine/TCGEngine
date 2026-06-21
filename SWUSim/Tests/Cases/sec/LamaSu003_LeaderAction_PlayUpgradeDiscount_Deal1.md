# SEC_003 Lama Su (leader) — Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle
# unit. It costs 1 resource less. If you do, deal 1 damage to that unit.
# P1 plays SOR_070 (Vigilance upgrade, +1/+1, cost 2 → 1 with the discount; base JTL_019 covers Vigilance)
# onto the only friendly non-Vehicle unit SOR_095 (3/3 → 4/4), then deals 1 to it (DAMAGE:1).
# 4 ready → 3 (paid 1, proving the −1: full cost 2 would leave 2).

## GIVEN
P1LeaderBase: SEC_003/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_070
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_070
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:3
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
