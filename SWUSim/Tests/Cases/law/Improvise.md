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
