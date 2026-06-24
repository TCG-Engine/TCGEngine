# ASH_015 Emperor Palpatine — Leader Action [Exhaust]: choose an exhausted friendly unit; give it an
# Advantage token for each OTHER friendly unit. SEC_135 (exhausted, the only valid target) gets 2 Advantage
# (SOR_095 and SOR_046 are the two other friendly units); Palpatine exhausts.
## GIVEN
P1LeaderBase: ASH_015/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1LEADER:EXHAUSTED
