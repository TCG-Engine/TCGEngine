# FirstGambitDiscount
#// LAW_229 The Master Codebreaker — "the first Gambit card you play each round costs 1 resource less."
#// With LAW_229 in play, SEC_211 (Gambit, Cunning/Heroism, cost 2) plays for 1 (off only by the discount):
#// with just 1 ready resource it leaves hand for discard and ends at 0 ready (empty deck -> search fizzles).

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
