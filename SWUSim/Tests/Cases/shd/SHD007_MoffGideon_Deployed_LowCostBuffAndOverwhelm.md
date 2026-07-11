# SHD_007 Moff Gideon (deployed) — "Each friendly unit that costs 3 or less gets +1/+0 and gains
# Overwhelm while attacking an enemy unit." Deployed (5 resources), SOR_095 (cost 2, power 3 → 4 with
# the buff) attacks SOR_160 (2 HP): 4 − 2 = 2 excess spills to P2's base via the granted Overwhelm.
# The base damage of 2 confirms both the +1 (without it, 3 vs 2 HP = 1 excess) and the Overwhelm grant.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
