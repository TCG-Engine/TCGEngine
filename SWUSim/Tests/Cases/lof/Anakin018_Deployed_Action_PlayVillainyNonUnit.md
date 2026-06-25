# LOF_018 Anakin Skywalker (deployed) — Action [use the Force]: play a Villainy non-unit card from
# your hand, ignoring its aspect penalties. Anakin spends the Force and plays the Villainy event
# SHD_243 (cost 1); it goes to discard.

## GIVEN
CommonSetup: bgw/brk/{
  myLeader:LOF_018;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
WithP1Hand: SHD_243
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1
