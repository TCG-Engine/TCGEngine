# SOR_012 IG-88 — deployed leader unit's passive: Each OTHER friendly unit gains Raid 1
# (+1/+0 while attacking). IG-88 is deployed (ground); a friendly space unit (Distant
# Patroller, 2 power) attacks the enemy base and deals 2 + 1 (Raid) = 3. (The Raid grant is
# already implemented in GetConditionalKeyword_Raid_Value — this test verifies it.)

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:SOR_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_060:1:0     # gains Raid 1 from deployed IG-88

## WHEN
- P1>DeployLeader
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3
