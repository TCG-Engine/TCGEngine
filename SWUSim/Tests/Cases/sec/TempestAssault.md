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

---

# NoEnemyBaseDamage_NoEffect
#// SEC_144 Tempest Assault — the AoE is gated on having dealt damage to an ENEMY BASE this phase. Here P1
#//   only attacks an enemy ground UNIT (base untouched), so playing Tempest Assault has no effect: the two
#//   enemy space units take no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_144

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:DAMAGE:0
P1NODECISION
