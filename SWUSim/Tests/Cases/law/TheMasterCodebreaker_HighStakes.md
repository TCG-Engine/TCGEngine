# FirstGambitDiscount
#// LAW_229 The Master Codebreaker — "the first Gambit card you play each round costs 1 resource less."
#// With LAW_229 in play, SEC_211 (Gambit, Cunning/Heroism, cost 2) plays for 1 (off only by the discount):
#// with just 1 ready resource it leaves hand for discard and ends at 0 ready (empty deck -> search fizzles).
#// COVERAGE: offer=SearchWindowIncludesEighthCard + SearchWindowExcludesNinthCard (the top-deck search
#//           pool is asserted behaviorally: the in-window Gambit is takeable, the out-of-window one
#//           resolves to nothing even when named; the search prompt is not an MZ pool, so no
#//           SELECTABLEEXACT applies) · reqboundary=SearchGambit (the search is answered on a later
#//           request after the play) · control=N/A (no control-change interaction; the discount is a
#//           static friendly aura) · boundary=SearchWindowIncludesEighthCard vs
#//           SearchWindowExcludesNinthCard (position 8 vs 9); FirstGambitDiscount vs
#//           SecondGambitNotDiscounted (first vs second Gambit); SearchEmptyDeckDoesNothing ·
#//           decline=SearchTakeNothingNoGambit (take nothing).

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
WithP1GroundArena: LAW_229:1:0
WithP1Hand: SEC_211

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# SearchGambit
#// LAW_229 The Master Codebreaker (Cunning, cost 2) — When Played: search the top 8 cards for a Gambit
#// card, reveal it, and draw it. SOR_223 (Gambit) is the match; SOR_237 is left.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: SOR_223
WithP1Deck: SOR_237
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# SearchTakeNothingNoGambit
#// LAW_229 The Master Codebreaker — When Played search of the top 8 finds no Gambit card (deck is SOR_095
#// and SOR_237, neither a Gambit), so nothing is selectable and the player takes nothing: no card is drawn
#// and Codebreaker resolves into the ground arena.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SearchEmptyDeckDoesNothing
#// LAW_229 The Master Codebreaker — When Played with an empty deck: the search fizzles with no prompt and
#// Codebreaker still resolves into play; nothing is drawn.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SecondGambitNotDiscounted
#// LAW_229 The Master Codebreaker — only the FIRST Gambit card each round is reduced by 1. With Codebreaker
#// already in play and two Gambit events (SEC_211, cost 2) in hand, the first plays for 1 and the second
#// for the full 2 -> 3 resources spent total; both events go to discard (empty deck fizzles their search).

## GIVEN
CommonSetup: yyw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_229:1:0
WithP1Hand: [SEC_211 SEC_211]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:2

---

# NonGambitNotReduced
#// LAW_229 The Master Codebreaker — the discount applies only to Gambit cards. With Codebreaker in play,
#// a non-Gambit unit (SOR_095 Battlefield Marine, cost 2) still costs the full 2, leaving 0 ready.

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: LAW_229:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:2

---

# SearchWindowIncludesEighthCard
#// LAW_229 The Master Codebreaker — the When Played search window is exactly the top 8 cards. A Gambit
#// card (SOR_223) sitting at position 8 (the last card inside the window) is found and drawn; the deck
#// keeps the other 8 cards.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_223 SOR_095]
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_223
P1DECKCOUNT:8
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SearchWindowExcludesNinthCard
#// LAW_229 The Master Codebreaker — the boundary pair to the section above: when the ONLY Gambit card
#// (SOR_223) sits at position 9, one past the 8-card window, it cannot be taken. The search decision is
#// answered with SOR_223 by name, but since that card is outside the peeked window the answer resolves
#// to taking nothing: no card is drawn and the deck keeps all 9 cards, the never-peeked SOR_223 on top
#// (the 8 peeked cards go to the bottom).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_223]
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:9
P1DECKTOPCARD:SOR_223
P1GROUNDARENAUNIT:0:CARDID:LAW_229
