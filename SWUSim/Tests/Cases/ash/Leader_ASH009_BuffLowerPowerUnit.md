# ASH_009 Ahsoka Tano — Leader Action [Exhaust]: choose a unit with less power than a friendly unit; it
# gets +2/+0 for this phase. SOR_038 (5 power) is the high friendly; SOR_095 (3 power < 5) is the only
# valid target (auto-resolved) and is buffed to 5.
## GIVEN
P1LeaderBase: ASH_009/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1LEADER:EXHAUSTED
