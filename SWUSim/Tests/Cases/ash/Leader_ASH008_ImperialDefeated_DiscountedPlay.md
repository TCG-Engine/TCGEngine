# ASH_008 Moff Gideon — Leader Action [Exhaust]: if a friendly Imperial unit was defeated this phase, play
# a unit from your hand costing 1 less. A SEC_080 (Imperial) dies attacking SOR_038, then Gideon plays the
# hand SEC_080 (cost 2, Command/Villainy — on-aspect) for 1 resource (proving the -1 discount: 2 → 1 left).
## GIVEN
CommonSetup: ggk/brk/{
  myLeader:ASH_008
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_038:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
