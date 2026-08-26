# OnAttack_MayDealOneToEnemyCreature
#// HMW_185 Ty Yorrick, Monster Hunter — Unit, Ground, cost 5, 4/5, [Aggression], unique,
#// traits Force / Bounty Hunter.
#// Text: "If a friendly ability would deal damage, you may have that ability deal that much damage
#//        plus 1 instead.
#//        On Attack: You may deal 1 damage to a Creature unit."
#//
#// ⚠⚠ THIS FILE COVERS THE SECOND CLAUSE ONLY. The first clause (the friendly-ability damage
#//    replacement) is NOT implemented — it is the engine's first source-side damage-increase
#//    replacement and needs shared infrastructure across four separate damage funnels plus a ruling on
#//    granularity; it is surfaced for a decision rather than guessed at. No section here asserts it, so
#//    nothing in this file is red-by-design. See the note in SWUSim/docs/hmw-implement.md.
#//
#// COVERAGE (clause 2): offer=OfferIsExactlyTheCreatureUnits — three legal Creatures across three
#//           arenas plus two non-Creatures and Ty himself, asserted as a pending pool ·
#//           decline=Decline_NoDamageDealt (MZMAYCHOOSE '-') and the distinct
#//           NoCreatureInPlay_NoOfferAtAll (a fizzle-only optional must not be offered at all) ·
#//           boundary=LethalToAOneHpCreature (exactly 1 damage kills a 1-HP Creature) paired with
#//           EnemyCreature (1 damage on a 5-HP body leaves it alive) ·
#//           control=N/A — the ability names no owner-scoped zone and no controller: "a Creature unit"
#//           is unqualified, so it reaches both sides (proven by MayDealOneToFriendlyCreature), and Ty
#//           is a non-leader unit with no When Defeated, so a take-control-then-act scenario exercises
#//           no different code ·
#//           reqboundary=RequestBoundary_TargetSurvivesTheBoundary ·
#//           modes=2P only for clause 2 (no player reference and no friendly/enemy wording — "a
#//           Creature unit" names neither side). ⚠ Clause 1 DOES say "a friendly ability" and would
#//           earn a Team Suns section; that goes with clause 1 when it is built.
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md.
#// ⚠ Every section below that actually deals the On Attack ping carries an extra `AnswerDecision:NO`:
#//   the ping is ITSELF a friendly ability, so clause 1 offers to make it 2. Declining keeps these
#//   sections about clause 2 alone; OwnPingIsAFriendlyAbility_BoostedToTwo is the accepting case.
#//
#// Ty attacks the enemy BASE (On Attack is not gated on what he attacks) and pings the enemy Creature
#// LOF_168 (8/5) for 1. The base takes his full 4.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:HMW_185
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# MayDealOneToFriendlyCreature
#// HMW_185 — "a Creature unit" carries NO friendly/enemy qualifier, so it reaches the caster's OWN
#// Creatures too. Ty pings the friendly LOF_168 rather than anything of the opponent's.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP1GroundArena: LOF_168:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:CARDID:LOF_168
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# MayDealOneToASPACECreature
#// HMW_185 — "a Creature unit" is not arena-restricted either. Ty is a GROUND unit attacking a base,
#// and LOF_119 is a SPACE Creature; it is still a legal target. An arena-scoped target pool (the easy
#// mistake, since every other part of this attack is in the ground arena) would miss it entirely.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: LOF_119:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P2SPACEARENAUNIT:0:CARDID:LOF_119
P2SPACEARENAUNIT:0:DAMAGE:1

---

# Decline_NoDamageDealt
#// HMW_185 — "You may", so the offer must be declinable even with exactly one legal Creature (an
#// MZMAYCHOOSE never auto-resolves). Declining costs nothing: the attack still resolves in full.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoCreatureInPlay_NoOfferAtAll
#// HMW_185 — DECLINE and NO-LEGAL-TARGET are different branches. With no Creature anywhere on the
#// board the optional ping can only fizzle, so it must not be OFFERED at all — not offered and then
#// declined. Asserts the absence of the prompt, not merely the absence of damage.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OfferIsExactlyTheCreatureUnits
#// HMW_185 — the OFFER cell. Answering a target proves the branch, never the pool, so this section
#// leaves the choose PENDING and asserts the pool itself. The board deliberately contains every kind
#// of near-miss: a friendly Creature (LOF_168), a friendly SPACE Creature (LOF_119), an enemy Creature
#// (LOF_143), a friendly NON-Creature (SOR_095), an enemy NON-Creature (SEC_080), and TY HIMSELF —
#// who is Force / Bounty Hunter, not a Creature, and so must be excluded on the trait even though the
#// text says "a Creature unit" rather than "another".
#// Pool order is the collector's: my ground, my space, their ground, their space.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP1GroundArena: LOF_168:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: LOF_119:1:0
WithP2GroundArena: LOF_143:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0&theirGroundArena-0

---

# LethalToAOneHpCreature
#// HMW_185 — boundary, low side: 1 damage is exactly lethal to the 4/1 Creature LOF_143, which is
#// defeated. Paired with OnAttack_MayDealOneToEnemyCreature, where the same 1 damage leaves a 5-HP
#// Creature standing.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: LOF_143:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# FiresWhenAttackingAUnitToo
#// HMW_185 — "On Attack" is not "when this unit attacks a base". The trigger must fire on a UNIT
#// attack as well; a handler wrongly gated on the attack's TARGET is the recurring shape here.
#// Ty attacks SEC_080 (3/3) and kills it while pinging the OTHER enemy unit, the Creature LOF_168.
#// ⚠ The On Attack resolves BEFORE combat damage, so LOF_168 is still at index 1 when it is chosen;
#//   after SEC_080 dies it compacts down to index 0, which is where it is asserted.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:HMW_185
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# RequestBoundary_TargetSurvivesTheBoundary
#// HMW_185 — the request-boundary cell. The On Attack fires mid-combat and its target choose is a real
#// interactive decision, so in production the answer arrives in a fresh process with the attack still
#// suspended. Identical to OnAttack_MayDealOneToEnemyCreature with one SimulateRequestBoundary before
#// the answer: the ping must still land and the attack must still finish.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# OwnPingIsAFriendlyAbility_BoostedToTwo
#// HMW_185 — the two clauses meet. Ty's own On Attack ping is itself "a friendly ability", so clause 1
#// offers to make it 2. This is the accepting counterpart to every clause-2 section above, which
#// declines in order to stay about clause 2 alone.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Clause1_EventDamage_OpenFireFourBecomesFive
#// HMW_185 clause 1, the plainest case: SOR_172 Open Fire ("Deal 4 damage to a unit") becomes 5.
#// The target is the enemy SOR_046 (3/7), which survives either way — so the assertion is the NUMBER,
#// not the outcome.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:HMW_185
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Clause1_Decline_DamageIsUnchanged
#// HMW_185 clause 1 — "you MAY". Declining leaves Open Fire at its printed 4. Same board as
#// Clause1_EventDamage_OpenFireFourBecomesFive, answered NO: the pair is what pins the +1 to the
#// player's choice rather than to the card simply being in play.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# Clause1_NoTyInPlay_NoOfferAtAll
#// HMW_185 clause 1 — the control for the whole family. Identical Open Fire board with NO Ty Yorrick:
#// the damage is 4 and no question is ever asked. Without this, every "+1" section above could be
#// satisfied by an unconditional increase that has nothing to do with this card.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# Clause1_UnitWhenPlayed_ThreeBecomesFour
#// HMW_185 clause 1 on a UNIT's ability rather than an event: SOR_132's "When Played: You may deal 3
#// damage to a space unit" becomes 4. Two prompts stack here — the card's own "you may" target choose,
#// then Ty's "+1?" — which is the shape a player actually sees.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_132
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:4

---

# Clause1_DividedDamage_PoolSixBecomesSeven
#// HMW_185 clause 1 on DIVIDED damage — SOR_135 Emperor Palpatine's "Deal 6 damage divided as you
#// choose among enemy units" becomes SEVEN to divide.
#// USER RULING 2026-08-26: the +1 goes on the POOL, not on each assigned share.
#// ⚠ Note the ORDER: the +1 has to be settled BEFORE the assignment is offered, so Ty's YESNO comes
#//   first and the split answer second.
#// This section cannot pass with a pool of 6: MZSPLITASSIGN is not in "up to" mode here, so the server
#// requires the full pool to be assigned and a 4+3 answer against a 6-point pool is rejected.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_135
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:4,theirGroundArena-1:3

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# Clause1_LeaderAction_OneBecomesTwo
#// HMW_185 clause 1 on a LEADER's ability — ASH_011's "Action [Exhaust]: Deal 1 damage to a unit with
#// 2 or more remaining HP" becomes 2. A leader ability is an ability like any other.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:ASH_011:1}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# Clause1_LeaderReaction_IndirectOneBecomesTwo
#// HMW_185 clause 1 on INDIRECT damage, reached through a REACTION: JTL_009 Boba Fett's "When you deal
#// non-combat damage: you may exhaust this leader. If you do, deal 1 indirect damage to a player."
#// P1 plays TWI_170 at the enemy base for its printed 2 (Ty declined), which is non-combat damage and
#// so wakes Boba; his 1 indirect is then boosted to 2. P2 controls no units, so the assignment
#// auto-resolves onto their base. Total 2 + 2 = 4.
#// ⚠ Boba's text is "deal 1 indirect damage to A PLAYER", so an OPTIONCHOOSE (You / Opponent) sits
#//   BETWEEN his exhaust question and Ty's +1. Omitting it does not fail loudly — the OPTIONCHOOSE
#//   silently takes its FIRST option ("You") when fed an unrecognised answer, which sends the whole
#//   indirect pool into P1's OWN base and leaves Ty's question dangling. Count prompts against answers.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_009:1;myResources:1}
P1OnlyActions: true
WithP1Hand: TWI_170
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# Clause1_StacksWithHuntingAggressor_SixToTheBase
#// HMW_185 clause 1 — the composite, and the proof that Ty and JTL_165 Hunting Aggressor STACK rather
#// than one overriding the other. Same board as Clause1_LeaderReaction_IndirectOneBecomesTwo plus a
#// friendly Hunting Aggressor, with Ty accepted on BOTH damage events:
#//   TWI_170 to the base:   2 printed + 1 (Ty)                        = 3
#//   Boba's indirect:       1 printed + 1 (Hunting Aggressor) + 1 (Ty) = 3
#// P2 controls no units, so both land on the base: 6 total.
#// ⚠ Hunting Aggressor's increase is automatic and applies FIRST (it is already at the top of
#//   SWUDealIndirectDamage); Ty's optional +1 is offered on the already-increased pool.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_009:1;myResources:1}
P1OnlyActions: true
WithP1Hand: TWI_170
WithP1GroundArena: HMW_185:1:0
WithP1SpaceArena: JTL_165:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:6
P1LEADER:EXHAUSTED

---

# Clause1_CombatDamageToAUnitIsNotAnAbility_NoOffer
#// HMW_185 clause 1 — THE load-bearing negative. The clause says "a friendly ABILITY", so combat
#// damage is excluded in both directions: neither Ty's own 4 power into the defender nor the
#// defender's 3 counter-damage back is offered a +1, and no question is asked at all.
#// (No Creature is in play, so the On Attack ping does not fire either — the only decision that could
#// exist here would be a wrongly-offered clause 1.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:HMW_185
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Clause1_CombatDamageToABaseIsNotAnAbility_NoOffer
#// HMW_185 clause 1 — the base half of the same negative. Combat damage to a base runs through the
#// SAME funnel as ability damage to a base (SWUDealDamageToBase), so it is only the gInCombatDamage
#// gate that keeps it out. Ty attacks the base for his printed 4 with no question asked.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
