# DiscardTop
#// LAW_242 Improvise — if you don't play the top card, you may discard it. Choose Discard -> the top
#// card is milled.

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard

## EXPECT
P1DECKCOUNT:0
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2

---

# PlayTopMinusOne
#// LAW_242 Improvise (Cunning event, cost 1) — "Look at the top card of your deck. You may play it. It
#// costs 1 resource less." Play the top SOR_237 (cost 2 -> 1).

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1DECKCOUNT:0
P1RESAVAILABLE:0

---

# Unaffordable_NoPlayOption
#// LAW_242 Improvise — "Look at the top card. You may play it (costs 1 less). If you don't, you may
#// discard it." The "Play" option must be gated on affordability: if the player can't pay the −1 cost,
#// only Discard / Leave should be offered (picking Play would just fizzle at resolve).
#//
#// Improvise costs 1 (Cunning, covered by Han/yellow base) → after playing it P1 has 0 ready resources.
#// Top card SOR_237 (cost 2, Heroism covered → no penalty) → 2 − 1 = 1 net > 0 → UNaffordable. Decision
#// left pending to read the offered options. (Companion: Improvise_PlayTopMinusOne covers the affordable
#// case, where Play IS offered — this fix must not remove it there.)

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONNOT:Play
P1OPTIONHAS:Discard
P1OPTIONHAS:Leave

---

# LeaveOnTop
#// LAW_242 Improvise — the third option leaves the top card untouched on top of the deck. Play Improvise,
#// then choose Leave: SOR_237 stays on top, deck size unchanged, only Improvise itself is discarded.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Leave

## EXPECT
P1DECKCOUNT:1
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeck_NoEffect
#// LAW_242 Improvise — with an empty deck there is no top card to look at, so the event resolves with no
#// effect and is simply discarded.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# PlayPiloting_NoVehicle_AsUnit
#// LAW_242 Improvise — a card with Piloting on top of the deck is played as a normal unit when there is no
#// eligible Vehicle to attach it to. Top card is JTL_196 Dagger Squadron Pilot (Piloting); with no friendly
#// Vehicle, choosing Play deploys it to the ground arena with no pilot prompt.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1Deck: JTL_196
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1DECKCOUNT:0

---

# PlayPiloting_WithVehicle_AsUnit
#// LAW_242 Improvise — when a friendly Vehicle exists, a Piloting card offers a Unit-or-Pilot choice.
#// Choosing Unit deploys JTL_196 to the ground arena; the vehicle SHD_195 gains no upgrade.

## GIVEN
CommonSetup: yyw/bgw/{myResources:4}
WithP1SpaceArena: SHD_195:1:0
WithP1Deck: JTL_196
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play
- P1>AnswerDecision:Unit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:0

---

# PlayPiloting_AsPilotUpgrade
#// LAW_242 Improvise — choosing Pilot instead plays JTL_196 as a Piloting upgrade on the friendly Vehicle
#// SHD_195, so no ground unit enters and the vehicle carries one upgrade.

## GIVEN
CommonSetup: yyw/bgw/{myResources:4}
WithP1SpaceArena: SHD_195:1:0
WithP1Deck: JTL_196
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SHD_195
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:0

---

# LooksAtYourOwnDeckNotOpponents
#// COVERAGE: control=LooksAtYourOwnDeckNotOpponents + PlayedByP2_UsesP2Deck +
#//           PlayedByP2_DiscardStaysOnP2Side — "the top card of YOUR deck" and the fallback discard both
#//           resolve from the seat that PLAYED the event; both seats are stocked with different decks so
#//           the wrong one is readable. Improvise is an Event with no board presence, so owner ≠
#//           controller is not constructible — it is only ever played from its controller's hand — and
#//           the axis is covered by seat-swap plus a stocked opposing deck · offer=Unaffordable_NoPlay-
#//           Option (OPTIONHAS/OPTIONNOT on the Play/Discard/Leave menu) · decline=LeaveOnTop ·
#//           reqboundary=N/A (the Play branch resolves inside the option answer).
#//
#// LAW_242 Improvise — P1 plays it with SOR_237 on top of P1's deck and a three-card SOR_225 deck sitting
#// on P2's side. Choosing Play must take P1's top card into P1's space arena and leave P2's deck at three
#// with nothing of P2's in play. Improvise itself is the only card in any discard, and it is P1's.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
P1OnlyActions: true
WithP1Deck: SOR_237
WithP2Deck: [SOR_225 SOR_225 SOR_225]
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P2DECKCOUNT:3
P2DISCARDCOUNT:0
P2SPACEARENACOUNT:0

---

# PlayedByP2_UsesP2Deck
#// LAW_242 Improvise played by P2 — "your deck" follows the seat that played it. P2's top card SOR_237 is
#// played into P2's space arena at 2 − 1 = 1 resource, emptying P2's deck; P1's three-card deck is
#// untouched and P1 has nothing in play and nothing in the discard. Every other section of this file runs
#// from P1, so this is the seat-swap witness.

## GIVEN
CommonSetup: bgw/yyw/{theirResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Deck: [SOR_225 SOR_225 SOR_225]
WithP2Deck: SOR_237
WithP2Hand: LAW_242

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Play

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:0
P1DECKCOUNT:3
P1DISCARDCOUNT:0
P1SPACEARENACOUNT:0

---

# PlayedByP2_DiscardStaysOnP2Side
#// LAW_242 Improvise played by P2 — the "if you don't, you may discard it" branch mills from P2's deck
#// into P2's discard: two cards there (Improvise plus the milled SOR_237) and P2's deck empty, while P1's
#// deck is still three cards and P1's discard is still empty. A mill run from the wrong seat would move
#// P1's counts instead.

## GIVEN
CommonSetup: bgw/yyw/{theirResources:1}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Deck: [SOR_225 SOR_225 SOR_225]
WithP2Deck: SOR_237
WithP2Hand: LAW_242

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Discard

## EXPECT
P2DECKCOUNT:0
P2DISCARDCOUNT:2
P2SPACEARENACOUNT:0
P1DECKCOUNT:3
P1DISCARDCOUNT:0
