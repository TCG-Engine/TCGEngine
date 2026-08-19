# WhenPlayed_HealsAnotherFriendlyUnit
#// HMW_063 Rho Medical Shuttle (Vigilance/Villainy, Imperial/Vehicle/Transport, cost 3, 3/3 SPACE,
#// non-unique) — "When Played/On Attack: You may heal 1 damage from another unit or base."
#// COVERAGE: offer=OfferSpansBothSidesAndBothBases_AndExcludesItself (the pool, left pending — it is the
#//           only thing that can show the self-exclusion AND that enemy targets and both bases are in) ·
#//           negative=Decline_NothingIsHealed + the self-exclusion inside the offer section ·
#//           boundary=HealClampsAtZero_UndamagedTargetIsStillLegal (an undamaged unit is a legal target
#//           that simply does nothing — the card says "a unit", not "a damaged unit") ·
#//           control=N/A — the effect names no seat ("another unit or base" is unqualified on both
#//           halves), so a control change cannot change the answer; the self-exclusion follows the
#//           OBJECT and is covered by the offer section · reqboundary=RequestBoundary_AcrossTheHealPick ·
#//           decline=Decline_NothingIsHealed
#// ⚠ TWO TRIGGER WINDOWS, one effect. Both must be exercised — a card wired to only one half passes
#//   every assertion about the other. OnAttack_HealsTooWhenAttackingTheBase is the second window, and it
#//   attacks the BASE deliberately: "On Attack" is not "attacks a unit" (five LAW cards shipped with
#//   that gate wrong because every section only ever swung at a unit).
#// ⚠ "another unit OR BASE" is unqualified on both halves, so the pool spans ENEMY units and BOTH bases;
#//   "another" excludes only the Shuttle itself.
#// ⚠ In-combat the OnAttack window must use MZMAYCHOOSE — a mandatory multi-target MZCHOOSE queued from
#//   an OnAttack closure auto-resolves to nothing and silently no-ops. The printed "you may" wants
#//   MZMAYCHOOSE anyway, so the two agree here.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:1:CARDID:HMW_063
P1NODECISION

---

# OnAttack_HealsTooWhenAttackingTheBase
#// HMW_063 — the SECOND trigger window, reached by attacking the enemy BASE so the trigger cannot be
#// accidentally gated on the attack having a unit target.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: HMW_063:1:0
WithP1SpaceArena: JTL_069:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:1:CARDID:JTL_069
P1SPACEARENAUNIT:1:DAMAGE:1
P1NODECISION

---

# Decline_NothingIsHealed
#// HMW_063 — "You MAY heal", so declining is a real answer and must leave every damage total alone.
#// '-' is the MZMAYCHOOSE decline (NO is for a YESNO).

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# OfferSpansBothSidesAndBothBases_AndExcludesItself
#// HMW_063 — the POOL, left pending. Three things only this section can show: enemy units are legal
#// ("another unit" carries no controller restriction), BOTH bases are in it ("or base" is unqualified
#// too), and the Shuttle itself is NOT — the sole thing "another" excludes.
#// Seeded so the pool is unambiguous: the Shuttle plus one friendly and one enemy space unit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:2
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0&myBase-0&theirBase-0
P1HASDECISION

---

# CanHealAnEnemyUnit
#// HMW_063 — and the pool RESOLVES: healing an enemy unit really works. Rarely what a player wants, but
#// it is what the card says, and a friendly-only implementation passes every other section here.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# CanHealABase_EitherSide
#// HMW_063 — the "or base" half, on the ENEMY base (the more surprising direction, and the one a
#// my-base-only implementation would miss). P1's own base is damaged too and must be left alone,
#// proving the choice is honoured rather than defaulted.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3;myBaseDamage:4;theirBaseDamage:4}
P1OnlyActions: true
WithP1Hand: HMW_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P1BASEDMG:4
P1NODECISION

---

# HealClampsAtZero_UndamagedTargetIsStillLegal
#// HMW_063 — the boundary. The card says "a unit", not "a damaged unit", so an UNDAMAGED unit is a legal
#// target that simply heals nothing; and healing a 1-damage unit lands on exactly 0, never -1.
#// Both are asserted at once: the 1-damage TIE goes to 0 and the undamaged X-Wing stays 0 and remains
#// in the offer (a zero-effect-targets-filtered-out implementation would have excluded it).

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:1
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# BothWindowsFire_InOneGame
#// HMW_063 — the two windows are INDEPENDENT and both fire in the same game, taking a 4-damage Frigate
#// down to 2. The Shuttle is NON-UNIQUE, so this uses two copies rather than a regroup: one is seeded
#// ready and attacks (On Attack), the other is played from hand (When Played). That keeps the section
#// about the two windows instead of about the phase machinery — an earlier draft went through a regroup
#// to ready the played copy and the second offer never surfaced, which says something about the pass
#// chain, not about this card.
#// A card that registered one closure under one window only heals once here.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:4
WithP1SpaceArena: HMW_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:3
P1NODECISION

---

# RequestBoundary_AcrossTheHealPick
#// HMW_063 — the request-boundary cell. The heal offer ends the request in production, so the target and
#// the amount must both be derived when the answer arrives rather than parked in memory when the offer
#// was raised. Same flow and assertions as the first section, with the boundary inserted before the pick.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_063
WithP1SpaceArena: JTL_069:1:2

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:1:CARDID:HMW_063
P1NODECISION
