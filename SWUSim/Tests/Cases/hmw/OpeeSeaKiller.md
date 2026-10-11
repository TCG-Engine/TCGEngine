# NabooBase_GainsGrit_PowerScalesWithDamage
#// HMW_090 Opee Sea Killer (Vigilance, Creature, 5-cost 5/6 Ground, non-unique) —
#// "While you control a Naboo base, this unit gains Grit. (This unit gets +1/+0 for each damage on it.)"
#// COVERAGE: offer=N/A (a continuous self keyword grant — nothing is ever selected) ·
#//           decline=N/A (no "you may") · boundary=N/A (no numeric threshold; the Grit arithmetic is the
#//           shared keyword's, and the ON/OFF pair NabooBase_… vs NonNabooBase_… is what pins the gate) ·
#//           control=OwnerHasNaboo_ControllerDoesNot_NoGrit + ChangeOfHeart_StolenOntoANabooBoard_GainsGrit
#//           (both directions of owner-vs-controller, one seeded, one a LIVE transition) ·
#//           reqboundary=N/A (structural: nothing is written — the grant is recomputed from the
#//           controller's base trait on every read, so there is no state to carry across a request) ·
#//           modes=2P only ("you control" is self-only in every format; no player reference, no
#//           friendly/enemy wording — a teammate's Naboo base does not satisfy "you control")
#// ⚠ PREVIEW-SET ASSUMPTION (HMW is absent from card-specific-rulings.md): "a Naboo base" is read off the
#// base's TRAIT (CardTraitSupplement), not a CardID list — three Naboo bases exist (JTL_023 Theed Palace,
#// JTL_031 Lake Country, HMW_033 Otoh Gunga) and this file uses all three.
#// Here: Theed Palace, 3 damage → Grit, power 5 + 3 = 8. HP is untouched by Grit (power only), and the
#// DAMAGE line proves the 3 is on the unit, not merely that the power moved.

## GIVEN
CommonSetup: bbw/bgw/{myBase:JTL_023}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:3

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_090
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:6

---

# NonNabooBase_NoGrit_PrintedPower
#// HMW_090 — the negative that makes the gate load-bearing: the identical board on a non-Naboo base
#// (SOR_029 Administrator's Tower). No Grit, so 3 damage adds nothing: power stays at the printed 5.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:3

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_090
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:5

---

# AnotherNabooPrinting_OtohGunga_AlsoGrants
#// HMW_090 — a DIFFERENT Naboo base (HMW_033 Otoh Gunga, the set-mate) with a different damage count, so
#// an implementation keyed to one base CardID reds here. 1 damage → power 6.

## GIVEN
CommonSetup: bbw/bgw/{myBase:HMW_033}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:1

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# OpponentsNabooBase_DoesNotCount
#// HMW_090 — "while YOU control": the only Naboo base on the table is P2's. A read of "any base" (or of
#// the opponent's) would grant Grit here.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;theirBase:JTL_023}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:3

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5

---

# Attack_GritAddsToTheDamageDealt
#// HMW_090 — the REAL execution path: combat reads ObjectCurrentPower, so a damaged Opee on a Naboo base
#// (JTL_031 Lake Country, the third printing) hits for 5 + 2 = 7. Proves the keyword reaches combat, not
#// just the stat read.

## GIVEN
CommonSetup: bbw/bgw/{myBase:JTL_031}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7

---

# Attack_NoNaboo_DealsPrintedPower
#// HMW_090 — the combat pair: the same attack on a non-Naboo base deals the printed 5.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# OwnerHasNaboo_ControllerDoesNot_NoGrit
#// HMW_090 — control reading (a): P1 OWNS the Opee and has the Naboo base, but P2 CONTROLS it on a
#// non-Naboo base. "You" is the controller, so no Grit. An owner-scoped read would grant it.
#// (A Controlled seed carries no damage field, so this pins the keyword only; the power half of the
#// control axis is on the live-steal section below.)

## GIVEN
CommonSetup: bbw/bgw/{myBase:JTL_023;theirBase:SOR_029}
P1OnlyActions: true
WithP2GroundArenaControlled: HMW_090:1

## WHEN
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_090
P2GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# ChangeOfHeart_StolenOntoANabooBoard_GainsGrit
#// HMW_090 — control reading (b), as a LIVE transition rather than a seed: P2 owns a 2-damage Opee on a
#// non-Naboo base (no Grit there — see NonNabooBase_…); P1 plays SOR_224 Change of Heart and takes it onto
#// P1's Naboo board. It must gain Grit under the NEW controller — which also proves the grant is
#// RECOMPUTED, never stamped at entry. Power 5 + 2 = 7.
#// Aspects: P1 yyk covers Change of Heart (Cunning, cost 6).

## GIVEN
CommonSetup: yyk/bgw/{myBase:JTL_023;theirBase:SOR_029;myResources:6}
P1OnlyActions: true
WithP2GroundArena: HMW_090:1:2
WithP1Hand: SOR_224

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_090
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:7

---

# LostAllAbilities_NoGrit_EvenOnNaboo
#// HMW_090 — the grant is one of the unit's OWN abilities, so a unit that has lost all abilities (the
#// SOR_138 Force Lightning marker) has no Grit even on a Naboo base: 3 damage, power stays 5. Guards an
#// implementation that adds the bonus straight into ObjectCurrentPower, bypassing the keyword layer.

## GIVEN
CommonSetup: bbw/bgw/{myBase:JTL_023}
P1OnlyActions: true
WithP1GroundArena: HMW_090:1:3:SOR_138

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5
