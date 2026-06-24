# ASH_003 Baylan Skoll — Leader Action [1 resource, Exhaust]: give a friendly unit +2/+2 for this phase if
# it's the only unit you control in its arena. SOR_095 is alone in the ground arena (and the only valid
# target, auto-resolved), so it gets +2/+2 (3 → 5 power); Baylan exhausts and 1 resource is spent.
## GIVEN
P1LeaderBase: ASH_003/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
