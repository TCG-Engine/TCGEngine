# Deals2BaseAnd2Unit
#// SOR_134 Ruthless Raider (Space, cost 6) — When Played: deal 2 to an enemy base AND
#// 2 to an enemy unit. P2's base takes 2; P2's only unit (Consular Security Force) is
#// auto-chosen and takes 2.
#// COVERAGE: offer=WhenPlayed_Offer_EnemyUnitsOnly (pending SELECTABLEEXACT: enemy units only,
#//           both arenas) · decline=N/A (mandatory damage, no "you may") ·
#//           control=NGOR_WhenDefeated_ResolvesForNewController_HitsOriginalOwner ("enemy" is
#//           read from the defeat-time controller's seat) · boundary=WhenDefeated_Deals2BaseAnd2Unit
#//           (the trigger's other half, fired from a removal event) · reqboundary=covered by the
#//           pending-offer section (the pick survives to the end-state read)
#// COVERAGE (Phase C update): boundary pair also=ExactlyTwo_OverkillDoesNotSpillToTheBase (the unit
#//           clause deals exactly 2 and the overkill on a 1-HP target reaches nothing) vs
#//           BothTriggersInOneGame_BaseTakesFour (the two trigger types are independent — 2+2 on the
#//           base, 2+2 on the unit, in one game) · no-valid-target=NoEnemyUnits_BaseStillTakesTwo (the
#//           base clause resolves alone with no dangling decision) · clause independence=
#//           ShieldedEnemyUnit_AbsorbsTheTwo_BaseStillTakesTwo (preventing the unit half leaves the
#//           base half at its full 2) · decline=N/A confirmed (no "you may" on either clause; the pick
#//           is a mandatory choose whose only option set is enemy units)

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_Offer_EnemyUnitsOnly
#// SOR_134 Ruthless Raider — Intended: the 2-damage unit pick is ENEMY units only, either arena;
#// friendly units are never candidates. P1 plays the Raider with a friendly trooper on the
#// ground and two P2 units in play; the pick is left PENDING — exactly the two enemy units.
#// (The 2 to the enemy base is dealt unconditionally alongside.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
P2BASEDMG:2

---

# WhenDefeated_Deals2BaseAnd2Unit
#// SOR_134 Ruthless Raider — Intended: the WHEN DEFEATED half mirrors the When Played. P2 kills
#// the Raider with Rival's Fall (SHD_079, defeat a unit); the Raider's controller (P1) then
#// deals 2 to the enemy base and 2 to the sole enemy unit. The cross-player reaction queues on
#// P1's seat → P1>Drain surfaces it.

## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP1SpaceArena: SOR_134:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NGOR_WhenDefeated_ResolvesForNewController_HitsOriginalOwner
#// SOR_134 Ruthless Raider — Intended: No Glory, Only Results (JTL_043: take control, then
#// defeat) makes P2 the Raider's controller at the defeat, so the When Defeated resolves for
#// P2 — "an enemy base and an enemy unit" are now P1's. P1's base takes 2 and P1's sole unit
#// takes 2; the Raider still goes to its OWNER's (P1) discard.

## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1SpaceArena: SOR_134:1:0
WithP1GroundArena: SOR_046:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NoEnemyUnits_BaseStillTakesTwo
#// SOR_134 Ruthless Raider — the no-valid-target case for the SECOND clause. "Deal 2 damage to an enemy
#// base AND 2 damage to an enemy unit" is two independent effects, so with P2's board empty the base
#// half must still resolve for its full 2 while the unit half simply finds nothing. Nothing is left
#// pending for P1 to answer and the Raider is on the board unharmed. An impl that gated the whole
#// trigger on a legal unit target would leave P2's base clean here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:0

---

# ExactlyTwo_OverkillDoesNotSpillToTheBase
#// SOR_134 Ruthless Raider — the quantity boundary on both halves at once. The only enemy unit is a
#// Death Star Stormtrooper (3/1): the 2 damage defeats it with 1 point of overkill, and that overkill
#// must go NOWHERE — the base takes exactly the 2 from its own clause, never 3. Ability damage is not
#// Overwhelm, and this is the section a "deal the remainder to the base" mistake would fail.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# ShieldedEnemyUnit_AbsorbsTheTwo_BaseStillTakesTwo
#// SOR_134 Ruthless Raider — the two clauses are independent, so preventing one must not touch the
#// other. P2's Consular Security Force carries a Shield token: the unit half is fully absorbed (the
#// shield pops, no damage counter lands) while the base half still deals its 2. The discriminator
#// against an impl that computes one "total" and splits it afterwards.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# BothTriggersInOneGame_BaseTakesFour
#// SOR_134 Ruthless Raider — "When Played/When DEFEATED" is two separate triggers on one card, and both
#// must fire in the same game without one consuming the other. P1 plays the Raider (2 to P2's base, 2
#// to its Consular Security Force), then P2 kills it with Rival's Fall (SHD_079) and the When Defeated
#// half repeats the pair: P2's base ends on 4 and the Force on 4. A once-per-card guard, or a
#// When-Played implementation that also satisfied the When-Defeated slot, would stop at 2 and 2.

## GIVEN
CommonSetup: rrk/bbk/{myResources:6;theirResources:6}
SkipPreGame: true
WithP1Hand: SOR_134
WithP2Hand: SHD_079
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:4
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
