# LOF_013 Barriss Offee (deployed) — Action [use the Force]: play an event from your hand, costs 1
# resource less. Barriss spends the Force and plays Confiscate (SOR_251, cost 1 -> 0); it fizzles with
# no upgrades and goes to discard. NO self-exhaust on the deployed side (Force is the only cost).

## GIVEN
CommonSetup: byk/brk/{
  myLeader:LOF_013;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_013:1:0
WithP1Hand: SOR_251
WithP1Resources: 2

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:READY
