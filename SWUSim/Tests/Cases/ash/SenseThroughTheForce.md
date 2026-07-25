# CostMatches_GiveAdvantage
#// ASH_235 Sense Through the Force (Event, cost 2) — Choose a number, search the top 5 for a card, draw it;
#// if its cost is the chosen number, you may give 3 Advantage to a Force unit. P1 chooses 4, draws SOR_046
#// (cost 4 — a match), and gives 3 Advantage to the Force unit SOR_049.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1HANDCOUNT:1

---

# CostMismatch_NoAdvantage
#// ASH_235 Sense Through the Force — the Advantage rider only fires when the drawn card's cost equals the
#// chosen number. P1 chooses 5 but draws SOR_046 (cost 4 — no match), so no Advantage is given (the card is
#// still drawn).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:SOR_046
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1HANDCOUNT:1

---

# GiveAdvantage_DeclineChooseNothing
#// ASH_235 Sense Through the Force — the 3-Advantage rider is a "you may". Even on a cost match (chose 4, drew
#// SOR_046 cost 4), P1 may decline ('-'): the card is still drawn but no Advantage is given.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1HANDCOUNT:1

---

# TakeNothing_AllToBottom_NoDraw
#// ASH_235 Sense Through the Force — the search draw is optional ("Take nothing"/PASS). Declining draws no card
#// and moves the searched cards to the bottom of the deck. A distinct 6th card (SEC_028) seeded below the top
#// five becomes the new top, proving the top cards were bottomed. No card drawn → no Advantage rider.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SEC_028
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:6
P1DECKTOPCARD:SEC_028
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# GiveAdvantage_EnemyForceUnit
#// ASH_235 Sense Through the Force — the Advantage target is any Force unit, friendly OR enemy. With only an
#// enemy Force unit (SEC_028 Trayus Acolyte) in play, P1 may hand it the 3 Advantage tokens.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP2GroundArena: SEC_028:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1HANDCOUNT:1

---

# GiveAdvantage_ForceLeaderUnit
#// ASH_235 Sense Through the Force — a Force LEADER unit is a valid Advantage target. P1's deployed SOR_011
#// Grand Inquisitor (a Force leader) receives the 3 Advantage tokens.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235;myLeader:SOR_011:1:1:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# NoForceTarget_NoAdvantagePrompt
#// ASH_235 Sense Through the Force — the Advantage rider only offers Force units as targets. With a cost match
#// but only a non-Force unit (SOR_239 Rebel Pathfinder) in play, no Advantage prompt appears; the card is still
#// drawn.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_239:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1HANDCOUNT:1

---

# DeckLessThanFive
#// ASH_235 Sense Through the Force — the search works with fewer than 5 cards in the deck. A 3-card deck is
#// searched; drawing SOR_046 (cost 4) matches the chosen 4, so the Force unit SOR_049 gets 3 Advantage tokens.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1DECKCOUNT:2
P1HANDCOUNT:1

---

# EmptyDeck_NoOp
#// ASH_235 Sense Through the Force — with an empty deck there is nothing to search or draw; the card resolves
#// with no card drawn and no Advantage rider.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
SkipPreGame: true
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4
## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
