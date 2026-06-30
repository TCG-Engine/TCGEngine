# ASH_003 Baylan Skoll — the +2/+2 only applies to a unit ALONE in its arena. With two ground units,
# neither qualifies, so no buff is given (both stay at base power); the cost is still paid (Baylan exhausts,
# 1 resource spent).
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003
}
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
