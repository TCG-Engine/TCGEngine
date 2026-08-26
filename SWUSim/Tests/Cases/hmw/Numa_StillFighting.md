# Defending_CombatDamageReducedByOne
#// HMW_088 Numa, Still Fighting — Unit, Ground, cost 4, 4/4, [Vigilance], unique, traits Rebel/Twi'lek.
#// Text: "Restore 1 (When this unit attacks, heal 1 damage from your base.)
#//        If this unit would be dealt damage, prevent 1 of that damage"
#// COVERAGE: offer=N/A (no target choice, no optional branch — the prevention is a continuous replacement
#//           with no decision of its own) ·
#//           decline=N/A (nothing optional; "prevent 1" is mandatory and automatic) ·
#//           boundary=Combat_OneDamageFullyPrevented (1 → 0, the clamp) + Combat_TwoDamageBecomesOne (2 → 1)
#//           and the ability-path pair AbilityDamage_OneFullyPrevented / AbilityDamage_TwoBecomesOne ·
#//           control=EnemyControlledNuma_StillPrevents — the clause says "this unit", not "friendly", so it
#//           is object-scoped and must work for whoever controls her ·
#//           reqboundary=RequestBoundary_PreventionPersistsAcrossActions — the prevention holds no state
#//           across a decision (it is recomputed from the CardID at every damage instance), so this is
#//           insurance rather than a live risk; the cell is written anyway, per policy ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "this unit" is the only
#//           subject, so Twin Suns and Team Suns share one code path with Premier).
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. This is read as a plain continuous
#//   replacement effect applying to EVERY damage instance from any source (the released analogues are
#//   TWI_053 Finn's granted "for this phase" version and SHD_224 Boba Fett's Armor's "prevent 2").
#//   Indirect damage stays unpreventable (CR); see Indirect_UnpreventableTakesFullAmount.
#//
#// P2's LAW_124 (4/7) attacks Numa: 4 power − 1 prevented = 3 damage on Numa. Numa's own 4-power counter
#// is NOT reduced (the clause protects Numa only), and Restore does NOT fire on defence — P1's base stays
#// at 5 damage.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:4
P1BASEDMG:5

---

# Attacking_RestoreHealsBaseAndCounterDamageReducedByOne
#// HMW_088 — both clauses in one attack. Numa attacks LAW_124 (4/7): Restore 1 heals P1's base 5 → 4,
#// LAW_124 takes Numa's full 4 power, and Numa's counter-damage of 4 is reduced to 3.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# Combat_OneDamageFullyPrevented
#// HMW_088 — boundary, low side: a 1-damage instance is reduced to ZERO (clamped, never negative).
#// P2's Battle Droid token TWI_T01 (1/1) attacks Numa → Numa takes 0; the droid takes 4 and dies.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# Combat_TwoDamageBecomesOne
#// HMW_088 — boundary partner: a 2-damage instance leaves exactly 1. Without this pair, a "prevent ALL"
#// implementation would pass Combat_OneDamageFullyPrevented on its own.
#// P2's SOR_063 (2/4) attacks Numa → Numa takes 1; SOR_063 takes Numa's 4 and dies.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENACOUNT:0

---

# AbilityDamage_TwoBecomesOne
#// HMW_088 — the clause is unqualified ("would be dealt damage"), so it covers ABILITY damage as well as
#// combat; that is a different engine funnel (SWUDealDamageToUnit, not the combat path), so it needs its
#// own section. P1 aims its own SHD_178 Daring Raid ("Deal 2 damage to a unit or base") at Numa → 1 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AbilityDamage_OneFullyPrevented
#// HMW_088 — boundary, low side on the ABILITY funnel. P1's LAW_206 That's a Rock ("Deal 1 damage to a
#// unit") aimed at Numa is fully prevented; the enemy SEC_080 is seeded so the choose really prompts.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_206
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AnotherFriendlyUnit_NotProtected
#// HMW_088 — the load-bearing NEGATIVE. The clause reads "this unit", NOT "another friendly unit"
#// (that wording belongs to SEC_050 Vigil, which is the aura version and the obvious mis-implementation
#// here). With Numa in play, a friendly SOR_095 hit by SHD_178 must take the FULL 2 damage.
#// (Expected to pass before implementation — it asserts an ABSENCE — and it is what stops the prevention
#// from being written as a field aura.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1GroundArena: HMW_088:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# PerInstance_TwoAbilityHitsEachReducedSeparately
#// HMW_088 — "if this unit WOULD BE DEALT damage" is a continuous replacement that applies to EVERY
#// instance, not once per phase/turn. Two separate 2-damage hits leave 1 + 1 = 2 total, not 3 (which is
#// what a one-shot marker would produce).

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1Hand: SHD_178
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# RequestBoundary_PreventionPersistsAcrossActions
#// HMW_088 — the request-boundary cell. Identical to PerInstance_TwoAbilityHitsEachReducedSeparately with
#// one SimulateRequestBoundary inserted between the two damage actions. The prevention is recomputed from
#// the CardID at each instance and holds no in-memory state, so this is insurance: if it is ever rebuilt
#// as a per-unit counter/marker written by one action and read by the next, this section reds and the
#// per-instance one stays green.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1Hand: SHD_178
WithP1GroundArena: HMW_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Indirect_UnpreventableTakesFullAmount
#// HMW_088 — scope exclusion. Indirect damage is UNPREVENTABLE (CR; the engine writes Damage directly and
#// never routes it through the prevention funnel), so Numa's "prevent 1" must NOT reduce it. P2 plays
#// JTL_234 Torpedo Barrage (5 indirect, aimed at P1); P1 assigns 3 to Numa and 2 to its base → Numa takes
#// the FULL 3.
#// (Expected to pass before implementation — an absence guard — and it is the section that catches the
#// prevention leaking into the indirect path once it exists.)

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: JTL_234
WithP1GroundArena: HMW_088:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myGroundArena-0:3,myBase-0:2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:2

---

# Shield_KeptWhenPreventionFullyCoversTheHit
#// HMW_088 — replacement ORDERING (CR: the damaged unit's controller orders multiple preventions, and the
#// engine picks the least wasteful order). A 1-damage hit is fully covered by Numa's own "prevent 1", so
#// the Shield token must be KEPT for a bigger hit later.
#// P2's TWI_T01 (1 power) attacks a shielded Numa → 0 damage, shield still attached.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: HMW_088:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Shield_AbsorbsWhenPreventionCannotCover
#// HMW_088 — the ordering CONTROL for Shield_KeptWhenPreventionFullyCoversTheHit. A 4-damage hit is NOT
#// fully covered by "prevent 1", so the Shield absorbs the whole instance and is spent — the reduction
#// must not be allowed to shave the hit to 3 and then let the shield eat that.
#// (Expected to pass before implementation — the shield already absorbs everything — but without it the
#// "keep the shield" section could be satisfied by simply never consuming shields.)

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: HMW_088:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_088
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# EnemyControlledNuma_StillPrevents
#// HMW_088 — the control cell. The clause names "this unit", not "a friendly unit", so it is scoped to the
#// OBJECT and must apply no matter who controls her. Numa sits on P2's board; P1's SHD_178 deals 2 → 1.
#// This is the section that catches a controller-scoped implementation (the SEC_050 Vigil shape).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: HMW_088:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_088
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
