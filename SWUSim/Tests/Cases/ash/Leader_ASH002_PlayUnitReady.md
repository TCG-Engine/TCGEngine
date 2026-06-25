# ASH_002 Fennec Shand — Leader Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from
# your hand (paying its cost); it enters play ready. P1 exhausts SEC_135 (the cost, auto-chosen) and plays
# SOR_095 (auto-chosen), which enters the ground arena READY; Fennec exhausts.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_002
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED
