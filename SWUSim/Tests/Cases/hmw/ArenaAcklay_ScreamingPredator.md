# Defending_SurvivesTheHit_TwoToTheEnemyBase
#// HMW_156 Arena Acklay, Screaming Predator — Unit (Ground) 5/6, cost 5, [Aggression][Villainy], Creature,
#// unique. "When this unit is dealt damage and survives: Deal 2 damage to each enemy base."
#//
#// COVERAGE: offer=N/A (structural — nothing is chosen: "each enemy base" is a loop, not a pick)
#//           decline=N/A (structural — no "may"; the ability is mandatory)
#//           boundary=Defending_DefeatedByTheHit_NoDamage vs this section (survives on 3 remaining vs
#//                    dies at exactly 0) · quantity=PoggleCombo_TwoInstances_TwoTriggers (per INSTANCE)
#//           control=ControlChange_TheOwnersBaseIsNowAnEnemyBase (the trigger belongs to the CONTROLLER)
#//           reqboundary=Defending_AcrossARequestBoundary (the queued reaction drains in a fresh process)
#//           modes=2P,TwinSuns,TeamSuns (text says "each ENEMY base": a loop over every opponent —
#//                 TwinSuns_EveryOpponentsBase — and a teammate is not an enemy — TeamSuns_NotTheTeammate)
#//
#// ★ JUDGE RULING 2026-09-14 (CR 8.9 / 20.1): damage that is PREVENTED or reduced to 0 was never dealt, so
#// this does not trigger — ShieldAbsorbsTheHit_NoTrigger and ReducedToZero_NoTrigger.
#// Wired as HMW_169 Crosshair's clause 1 is: a self observer below _SWUOnUnitDamaged's $survived gate,
#// queued as a CUSTOM for the controller. "Dealt damage", not "combat damage" — every funnel counts.
#//
#// This section: P2's SEC_080 (3/3) attacks it. It survives on 3 remaining, and P2's base takes 2.
#// ⚠ P1>Drain: the reaction is queued on the DEFENDER's queue mid-combat; the drain is the harness
#// stand-in for production's post-action automation (the Crosshair sections do the same).

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_156:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_156
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:2
P1BASEDMG:0

---

# Defending_DefeatedByTheHit_NoDamage
#// "…and SURVIVES". Pre-damaged to 3 remaining HP, the 3 kills it: no base damage.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_156:1:3
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:0

---

# Attacking_TakesCounterDamageAndSurvives_Triggers
#// Its OWN attack counts too: attacking SOR_046 (3/7) it takes 3 back and survives. P2's base takes 2
#// (from the reaction — the attack was on a unit, so no combat damage reached the base).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_156:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:2

---

# ShieldAbsorbsTheHit_NoTrigger
#// ★ JUDGE RULING: a Shield prevents the damage, so none is DEALT — no trigger. The Shield pops, it stays
#// undamaged, and the enemy base is clean.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_156:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0

---

# PoggleCombo_AbilityDamage_Triggers
#// The deck it was previewed for. HMW_012 Poggle's front — "Ready a friendly Creature unit and deal 1
#// damage to it" — readies the exhausted Acklay with 1 on it; it survives, so P2's base takes 2.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_156:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:2

---

# ReducedToZero_NoTrigger
#// ★ JUDGE RULING, the non-Shield form: P1 also controls SEC_050 Vigil ("If damage would be dealt to
#// another friendly unit, prevent 1 of that damage"), so Poggle's 1 becomes 0. Nothing is dealt — the
#// Acklay is still readied (the "and" half), but no base takes damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_156:0:0
WithP1SpaceArena: SEC_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# PoggleCombo_TwoInstances_TwoTriggers
#// Per INSTANCE, not per phase. Poggle readies it with 1 (→ 2 to the base), then the readied Acklay
#// attacks SOR_046 and survives the 3 back (→ 2 more): P2's base ends on 4. Acklay ends on 4 damage of 6.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_156:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:4

---

# LostAllAbilities_NoTrigger
#// The reaction is its ability: under SOR_138 Force Lightning it survives the hit and nothing happens.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_156:1:0:SOR_138
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# ControlChange_TheOwnersBaseIsNowAnEnemyBase
#// "Each ENEMY base" is read from the CONTROLLER. P1 controls P2's Acklay; Poggle pings it, and the 2
#// goes to P2's base — its owner's, and now an enemy's — while P1's own base stays clean.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_156:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_156
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:2
P1BASEDMG:0

---

# Defending_AcrossARequestBoundary
#// The reaction is queued mid-combat on the defender's queue and drained in a later request.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_156:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>Drain

## EXPECT
P2BASEDMG:2
P1BASEDMG:0

---

# TwinSuns_EveryOpponentsBase
#// ⚠ CANNOT PASS AT TWO SEATS. "Each enemy base" is a LOOP over every opponent. The Acklay is parked on
#// SEAT 3 (a seat-1 reactor lets the two-seat OtherPlayer() shortcut look right). P2 plays SOR_172 Open
#// Fire on it for 4; it survives on 2 remaining, and seats 1, 2 and 4 each take 2 — seat 3's own base none.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
SkipPreGame: true
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP3GroundArena: HMW_156:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:p3GroundArena-0
- P3>Drain
- P1>Drain
- P2>Drain
- P4>Drain

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:4
P1BASEDMG:2
P2BASEDMG:2
P4BASEDMG:2
P3BASEDMG:0

---

# TeamSuns_NotTheTeammate
#// TEAM SUNS: an ENEMY base is an OPPONENT's. Seat 3's Acklay is on seat 1's team, so seats 2 and 4 take
#// 2 each and seat 1 (the teammate) takes nothing.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 2
SkipPreGame: true
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP3GroundArena: HMW_156:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:p3GroundArena-0
- P3>Drain
- P1>Drain
- P2>Drain
- P4>Drain

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:2
P4BASEDMG:2
P1BASEDMG:0
P3BASEDMG:0
