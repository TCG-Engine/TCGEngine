# DamagedBase_AoESpace
#// SEC_144 Tempest Assault (Event, Aggression/Villainy, cost 4) — "If you've dealt damage to an enemy
#//   base this phase, deal 2 to each enemy space unit." SOR_237 attacks P2 base (sets the flag), then
#//   SEC_144 deals 2 to each enemy space unit (two JTL_069s).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_144

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:1:DAMAGE:2
P1NODECISION
