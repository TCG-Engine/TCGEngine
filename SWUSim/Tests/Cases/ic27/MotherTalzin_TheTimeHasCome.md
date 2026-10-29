# PlaysVillainyFromDiscard_NotNight_TakesItsCost
#// IC27_066 Mother Talzin — The Time Has Come (5/5 Ground, Command/Villainy, Force·Night, unique)
#// "When Played: You may play a Villainy unit that costs 4 or less from your discard pile for free. If it's not a
#// Night or Undead unit, deal damage equal to its cost to this unit."
#// SHD_242's shape: an optional pick (MZMAYCHOOSE) over the Villainy units in P1's discard with printed cost <= 4,
#// free-played through SWUNestedPlay(ignoreCost). "If it's not a Night or Undead unit" reads the played card's
#// printed traits; the damage is its PRINTED cost (the free play cost 0), dealt by Talzin to herself — only if the
#// card actually left the discard (a refused play deals nothing).
#//
#// COVERAGE: offer=Offer_VillainyUnitsCostingFourOrLess ·
#//           decline=Decline_NothingPlayed_NoDamage ·
#//           boundary=Offer_VillainyUnitsCostingFourOrLess (cost 4 in, cost 5 out) + CostFour_FourDamage ·
#//           control=N/A ("your discard pile" is the resolver's; Talzin's When Played resolves for whoever played
#//           her) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P only ("your discard pile" — self-only)
#//
#// Talzin (7 resources -> 0) plays the Imperial Dark Trooper (cost 2, Imperial — not Night/Undead) from the discard
#// for free; she takes 2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:IC27_066
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# Offer_VillainyUnitsCostingFourOrLess
#// The pool: Villainy UNITS with printed cost <= 4 — Imperial Dark Trooper (2) and LOF_035 (4, Night). Excluded: Captain Phasma (Villainy, cost 5), Battlefield Marine (Heroism) and Confiscate (an event).

## GIVEN
CommonSetup: ggk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066
WithP1Discard: SEC_080
WithP1Discard: JTL_088
WithP1Discard: SOR_095
WithP1Discard: SOR_251
WithP1Discard: LOF_035

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-4

---

# NightUnit_NoDamage
#// LOF_031 is a Night unit: it is played and Talzin takes no damage.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:LOF_031}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_031
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# UndeadUnit_NoDamage
#// LOF_180 is an Undead unit (Sith, Undead): no damage either.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:LOF_180}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_180
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# CostFour_FourDamage
#// The damage is the played unit's PRINTED cost, not the 0 it cost to play: Admiral Motti (IC27_062, cost 4,
#// Imperial) -> 4 damage on Talzin's 5 HP. She survives on 1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:IC27_062}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:IC27_062
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Decline_NothingPlayed_NoDamage

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION

---

# NoLegalUnitInDiscard_NoPrompt
#// Only a Heroism unit and a cost-5 Villainy unit in the discard: nothing to offer.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066
WithP1Discard: SOR_095
WithP1Discard: JTL_088

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NestedPlay_ClosesTheActionOnce
#// Playing a card from inside a When Played is a nested play: the whole thing is ONE action, closed once.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;discardCardIds:SEC_080}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_066

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
TURNPLAYER:2
NOEXTRAACTION
