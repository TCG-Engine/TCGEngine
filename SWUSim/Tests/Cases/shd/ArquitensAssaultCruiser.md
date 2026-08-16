# BaseAttack_NoSteal
#// SHD_122 — attacking the base defeats no unit, so nothing is stolen (resource count unchanged).
#// COVERAGE: offer=N/A (mandatory, no target pick — the defeated unit IS the target) ·
#//           decline=N/A (not a "you may") · control=DefeatBecomesResource (the enemy-owned card enters
#//           P1's resource zone under P1's control while P2 keeps ownership) ·
#//           boundary=DefeatBecomesResource (defeat → steal) vs ShieldedSurvivor_NoSteal /
#//           BaseAttack_NoSteal / LeaderUnitDefender_NoSteal / TokenDefender_NoSteal (no steal), plus
#//           DefeatedByOnAttackAbility_StillBecomesResource (defeat by an ability, not combat damage) ·
#//           reqboundary=N/A (the trigger resolves inside the attack, with no player decision to serialize)

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:7
P2SPACEARENACOUNT:1
P1RESCOUNT:2

---

# DefeatBecomesResource
#// SHD_122 Arquitens Assault Cruiser (Unit, Space, cost 8, Command, 7/8, Ambush)
#//   "When this unit attacks and defeats a non-leader unit: Put the defeated unit into play as a resource
#//    under your control."
#// SHD_122 (7 power) attacks P2's TIE Fighter (SOR_225, 2/1) and defeats it. Instead of the TIE going to
#// P2's discard, it enters P1's resource zone (exhausted, controlled by P1) — so P2's discard stays empty
#// and P1's resource count rises by 1. SHD_122 survives the 2 counter-damage (DAMAGE:2).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:0
P1RESCOUNT:3
P1SPACEARENAUNIT:0:DAMAGE:2

---

# ShieldedSurvivor_NoSteal
#// SHD_122 — if the defender survives (a Shield absorbs the hit), it isn't defeated, so nothing is stolen.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:1
P1RESCOUNT:2

---

# LeaderUnitDefender_NoSteal
#// SHD_122 — the ability reads "attacks and defeats a NON-LEADER unit". A deployed LEADER unit that
#// SHD_122 attacks and defeats is therefore never resourced: it returns to its owner's leader zone
#// (undeployed), P2's discard stays empty and P1's resource count is unchanged.
#// Fixture: P2's leader is HMW_004 Grand Moff Tarkin, whose deployed side (The Death Star, 2/12) is a
#// SPACE unit, pre-damaged 5 so SHD_122's 7 power finishes it; its 2 power counters for DAMAGE:2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2; theirLeader:HMW_004:1:1:0:5}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2LEADER:NOTDEPLOYED
P2DISCARDCOUNT:0
P1RESCOUNT:2
P1SPACEARENAUNIT:0:DAMAGE:2

---

# TokenDefender_NoSteal
#// SHD_122 — a defeated TOKEN unit ceases to exist rather than going to a discard pile, so there is no
#// card to put into play as a resource. SHD_122 defeats P2's X-Wing token (JTL_T02, 2/2): the token
#// leaves the arena, P2's discard stays empty, and P1's resource count is unchanged.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: JTL_T02:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:0
P1RESCOUNT:2
P1SPACEARENAUNIT:0:DAMAGE:2

---

# DefeatedByOnAttackAbility_StillBecomesResource
#// SHD_122 — "attacks and DEFEATS" is satisfied by a defeat at ANY point in the attack, not just by
#// combat damage. SHD_122 carries JTL_172 Twin Laser Turret ("On Attack: Deal 1 damage to each of up to
#// 2 units in this arena") and attacks P2's first SOR_225 (2/1). The On Attack kills BOTH TIEs before
#// combat damage: the DEFENDER is still "defeated by the attacker" and becomes P1's resource, while the
#// bystander TIE just goes to P2's discard. With the defender already gone there is no counter-damage,
#// so SHD_122 ends undamaged.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP1SpaceArenaUpgrade: 0:JTL_172
WithP2SpaceArena: [SOR_225:1:0 SOR_225:1:0]

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirSpaceArena-0&theirSpaceArena-1

## EXPECT
P2SPACEARENACOUNT:0
P1RESCOUNT:3
P2DISCARDCOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:0
