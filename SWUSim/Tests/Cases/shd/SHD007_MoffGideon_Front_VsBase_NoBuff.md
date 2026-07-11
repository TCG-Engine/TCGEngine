# SHD_007 Moff Gideon — the +1/+0 only applies when attacking a UNIT. With no enemy units, the ≤3-cost
# attacker (SOR_095, power 3) hits the base for 3 (no +1).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
