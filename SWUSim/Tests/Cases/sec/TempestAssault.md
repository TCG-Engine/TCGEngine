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

---

# DamageToYOUROwnBase_DoesNotArmTheAoE
#// SEC_144 Tempest Assault — the gate is "damage to an ENEMY base". Damage P1 deals to P1's OWN base
#// (SEC_164 Warrior of Clan Ordo's self-damage when the disclose is declined) must not satisfy it, so
#// Tempest Assault does nothing to the enemy space units.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_144
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:DAMAGE:0

---

# EnemyBaseDamagedByAnABILITY_AlsoArmsTheAoE
#// SEC_144 Tempest Assault — "you've DEALT damage to an enemy base this phase" is not combat-only. Here
#// the base damage comes from a card ability rather than an attack — P1 plays SHD_178 Daring Raid at
#// P2's base for 2 — and Tempest Assault still fires for 2 into each enemy space unit.

## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SHD_178
WithP1Hand: SEC_144

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:1:DAMAGE:2

---

# TwinSuns_ANYEnemyBaseDamagedThisPhaseArmsIt
#// ⚠ TWIN SUNS SWEEP (2026-08-27) — the same existential gate as ASH_039, and the same harness blocker.
#// "If you've dealt damage to AN enemy base this phase" checked seat 2 alone. Here the damage goes to
#// SEAT 4's base, and the payoff ("deal 2 to each enemy space unit") lands on seat 2's ship — so the
#// section fails unless the gate looks past seat 2 AND the payoff still fans out to every opponent.
## GIVEN
CommonSetup: rrk/grw
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 4
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_144
## WHEN
- P1>AttackSpaceArena:0:P4B
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P2SPACEARENAUNIT:0:DAMAGE:2
