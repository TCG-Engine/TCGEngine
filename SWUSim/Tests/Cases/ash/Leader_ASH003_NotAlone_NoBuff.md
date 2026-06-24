# ASH_003 Baylan Skoll — the +2/+2 only applies to a unit ALONE in its arena. With two ground units,
# neither qualifies, so no buff is given (both stay at base power); the cost is still paid (Baylan exhausts,
# 1 resource spent).
## GIVEN
P1LeaderBase: ASH_003/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
