# ASH_011 Cad Bane — Leader Action [Exhaust]: deal 1 damage to a unit with 2 or more remaining HP. SOR_046
# (3/7) has 7 remaining HP (the only legal target, auto-resolved) and takes 1 damage; Cad Bane exhausts.
## GIVEN
P1LeaderBase: ASH_011/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
