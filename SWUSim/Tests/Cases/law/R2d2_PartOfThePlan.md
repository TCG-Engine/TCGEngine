# SearchSharedAspect
#// LAW_145 R2-D2 (1/3) — When Played: search the top 5 cards for a unit that shares an aspect with a
#// friendly unit, reveal it, and draw it. P1 controls SOR_063 (Vigilance); SOR_046 (Vigilance,Heroism)
#// shares -> drawn; SOR_225 (Villainy) does not.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_225
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# TakeNothingAfterSearch
#// LAW_145 R2-D2 (2/3) — after the search reveals a valid unit, the player may still decline ("take
#// nothing"). Same board as the happy path (SOR_046 shares Vigilance with SOR_063) but P1 declines with
#// `-`; nothing is drawn and both looked-at cards return to the bottom of the deck.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_225
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# AllInvalidNoSharedAspect
#// LAW_145 R2-D2 (3/3) — if no unit in the top 5 shares an aspect with a friendly unit, every card is
#// invalid and the player must take nothing. Friendly SOR_063 is Vigilance only; R2-D2 itself is
#// Command/Heroism. Deck holds SOR_225 (Villainy), SOR_164 (Aggression), SOR_128 (Aggression/Villainy),
#// LAW_231 (Cunning) — none share Vigilance, Command, or Heroism, so nothing can be drawn.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_225
WithP1Deck: SOR_164
WithP1Deck: SOR_128
WithP1Deck: LAW_231
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:4

---

# EmptyDeck_NoSearch
#// LAW_145 R2-D2 — with an empty deck the When Played search has nothing to look at and auto-passes (no
#// decision). R2-D2 still enters play; the board is just the seated friendly unit plus R2-D2.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENACOUNT:2

---

# StolenUnitCountsAsFriendlyForAspectMatch
#// COVERAGE: control=StolenUnitCountsAsFriendlyForAspectMatch (a P2-OWNED unit that P1 CONTROLS supplies
#//           a "friendly" aspect) + EnemyControlledOwnUnit_IsNotFriendly (a P1-OWNED unit that P2 CONTROLS
#//           does not) — "a friendly unit" is measured by CONTROL, and "your deck" by the ability
#//           controller's seat (both fixtures stock P2's deck and assert it untouched) · offer=
#//           EnemyControlledOwnUnit_IsNotFriendly (SEARCHPLAYABLE pool asserted with a positive control
#//           inside it, so an empty pool can't pass vacuously) · decline=TakeNothingAfterSearch ·
#//           reqboundary=N/A (single When Played decision; nothing is re-read after it).
#//
#// LAW_145 R2-D2 — owner ≠ controller. The only non-R2-D2 unit P1 controls is a SOR_164 Wampa (Aggression)
#// that P2 OWNS. It is still a FRIENDLY unit for P1, so its Aggression is a live aspect: SOR_128 (Aggression
#// /Villainy) shares it and is drawn, while SOR_225 (Villainy only) shares nothing with Aggression or with
#// R2-D2's own Command/Heroism. P2's deck is stocked with two SOR_095 and must be untouched — "your deck"
#// is the controller's.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_164:2
WithP1Deck: [SOR_128 SOR_225]
WithP2Deck: [SOR_095 SOR_095]
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DECKCOUNT:1
P2DECKCOUNT:2
P2HANDCOUNT:0

---

# EnemyControlledOwnUnit_IsNotFriendly
#// LAW_145 R2-D2 — the mirror. P1 OWNS the SOR_164 Wampa (Aggression) but P2 CONTROLS it, so its
#// Aggression is NOT available to R2-D2's "shares an aspect with a friendly unit". The only friendly unit
#// is R2-D2 himself (Command/Heroism). The search pool must therefore hold SOR_095 (Command/Heroism) and
#// exclude SOR_128 (Aggression/Villainy) — SOR_128 would only be legal if the away Wampa counted — and
#// SOR_225 (Villainy). The positive SOR_095 entry is what stops the two exclusions passing on an empty
#// pool. Decision left pending so the offer itself is what is asserted.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
P1OnlyActions: true
WithP2GroundArenaControlled: SOR_164:1
WithP1Deck: [SOR_128 SOR_095 SOR_225]
WithP2Deck: [SOR_046 SOR_046]
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_095
P1SEARCHPLAYABLENOT:SOR_128
P1SEARCHPLAYABLENOT:SOR_225
