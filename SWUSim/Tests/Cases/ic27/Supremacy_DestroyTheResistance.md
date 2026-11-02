# WhenPlayed_HalfOfAnUndamagedBase
#// IC27_110 Supremacy — Destroy the Resistance (10/10 Space, Aggression/Villainy, First Order·Vehicle·Capital Ship)
#// "When Played: Deal damage to a base equal to half its remaining HP, rounded down."
#// SWUOfferBaseTarget (every live seat's base, the caster's own included — "a base" has no qualifier) ->
#// IC27_110#0, which reads the chosen base's REMAINING HP at resolution (SWUBaseRemainingHp: real max HP minus
#// damage) and deals floor(remaining / 2).
#//
#// COVERAGE: offer=Offer_EveryBase_OwnIncluded ·
#//           decline=N/A (no "you may"; the base pick is mandatory) ·
#//           boundary=OddRemaining_RoundsDown (27 -> 13) + OneRemaining_DealsNothing (1 -> 0) ·
#//           control=N/A (no owner-scoped zone: "a base", chosen) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P,TwinSuns ("a base" is a choice among every player's base) · TwinSuns=TwinSuns_EveryBaseOffered
#//
#// P2's base is undamaged (30): Supremacy deals 15.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:15
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:IC27_110
P1NODECISION

---

# Offer_EveryBase_OwnIncluded

## GIVEN
CommonSetup: rrk/bbw/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# OddRemaining_RoundsDown
#// P2's base already has 3 damage: 27 remaining -> 13 more (not 14), 16 total.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10;theirBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:16

---

# OneRemaining_DealsNothing
#// 29 damage on a 30-HP base: 1 remaining -> half rounded down is 0. The base survives.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10;theirBaseDamage:29}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:29
P1NODECISION

---

# NonStandardBaseHp_ReadsTheRealRemaining
#// "remaining HP" reads the base's real HP: a 34-HP base (JTL_031) takes 17, not the 15 a 30-HP assumption gives.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10;theirBase:JTL_031}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:17

---

# OwnBase_CanBeChosen
#// "a base" includes your own: P1's base (4 damage, 26 remaining) takes 13.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10;myBaseDamage:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:17
P2BASEDMG:0

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: rrk/bbw/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:15

---

# TwinSuns_EveryBaseOffered
#// Twin Suns: the pick offers every live seat's base (P1's own and P2/P3/P4's), and a far-seat base takes it.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:2
WithP4Base: SOR_019:0
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myBase-0&p2Base-0&p3Base-0&p4Base-0

---

# TwinSuns_FarSeatBaseTakesIt
#// P3's base (SOR_019, a 25-HP base, with 2 damage: 23 remaining) takes 11 — 13 total.

## GIVEN
CommonSetup: rrk/bbw/{myResources:10}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:2
WithP4Base: SOR_019:0
WithP1Hand: IC27_110

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Base-0

## EXPECT
P3BASEDMG:13
P2BASEDMG:0
