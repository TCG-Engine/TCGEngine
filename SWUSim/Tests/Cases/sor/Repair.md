# HealsBase
#// SOR_074 Repair (Event, cost 1) — "Heal 3 damage from a unit or base." With no
#// units in play, the only targets are the two bases. P1 chooses its own base
#// (myBase-0), healing 3: base damage 5 → 2. (Proves bases are valid MZCHOOSE
#// targets via myBase-0 / theirBase-0.)
#// COVERAGE: offer=Offer_AllUnitsAndBothBases (pending SELECTABLEEXACT over every unit in all
#//           four arenas plus both bases — damage is NOT a targeting requirement) ·
#//           reqboundary=HealsBase + HealsThreeFromAnEnemyUnit (the target answer arrives in a
#//           separate request from the play; the queued HEAL_TARGET|3 reads the pick after the
#//           boundary) · control=N/A (heal is controller-agnostic; enemy-controlled units are in
#//           the offer and HealsThreeFromAnEnemyUnit heals one) · boundary pair=HealsBase (5→2,
#//           full 3 healed) + HealsTwoDamageToFull (2→0, heal caps at existing damage) ·
#//           decline=N/A (mandatory effect, no "you may")

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5;handCardIds:SOR_074}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2

---

# Offer_AllUnitsAndBothBases
#// Intended: the target pool is EVERY unit in play (both arenas, both sides) plus both bases —
#// damage is not a targeting requirement, so undamaged units and bases are still offered.
#// Only the enemy ground unit carries damage here; the pool must nonetheless contain all four
#// units and both bases. The decision is left pending so the exact offer can be inspected.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_074}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:3
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0&myBase-0&theirBase-0

---

# HealsThreeFromAnEnemyUnit
#// Resolution of the offer above: choosing the enemy Consular Security Force (3/7, damage 3)
#// heals the full 3 — damage 3 → 0. Healing an ENEMY unit is legal ("a unit", not "a friendly
#// unit"); the friendly units and both bases are untouched.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_074}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:3
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# HealsTwoDamageToFull
#// Intended: a target with only 1 or 2 damage is healed to FULL — "heal 3" caps at the damage
#// actually present (no underflow, no carry-over anywhere else). Unit with 2 damage → 0.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_074}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoDamageAnywhere_NoPrompt
#// USER RULING 2026-08-13: with zero damage on every unit and both bases the mandatory heal has no
#// meaningful choice — the prompt is skipped entirely (cost paid, event discarded, no decision).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SOR_074

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1

---

# DamageExists_UndamagedTargetsStillOffered_SoftPassHealZero
#// The other half of the ruling: with ANY damage present the pool is NOT filtered — undamaged targets
#// stay pickable, so a player may soft-pass by healing 0 from their own undamaged base while the
#// enemy Wampa keeps its 2 damage. Offer asserted in the sibling section Offer_AllUnitsAndBothBases;
#// here the zero-heal pick resolves.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:2
WithP1Hand: SOR_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:0
