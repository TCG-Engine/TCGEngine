# OnAttack_MatchDraw
#// SOR_238 C-3PO — On Attack window (same ability fires when C-3PO attacks). C-3PO (in play, ready,
#// power 1) attacks P2's base; the On Attack trigger resolves first: choose 2 (matches SOR_095) →
#// Draw → SOR_095 drawn (deck 3→2, hand 0→1). Then combat deals C-3PO's 1 power to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_238:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P2BASEDMG:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1

---

# WhenPlayed_MatchDraw
#// SOR_238 C-3PO (Unit 1/4, cost 2, Heroism) — When Played/On Attack: choose a number, then look at
#// the top card; if its cost is the chosen number, you may reveal and draw it. P1 plays C-3PO and
#// chooses 2 (blindly). The top card SOR_095 (Battlefield Marine) costs 2 → matches → the player is
#// offered the card and chooses Draw → SOR_095 is drawn (hand 0→1, deck 3→2).

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# WhenPlayed_MatchLeave
#// SOR_238 C-3PO — match but decline: P1 chooses 2 (matches SOR_095's cost 2), is offered the card,
#// and chooses Leave → nothing drawn, the card stays on top of the deck. ("you MAY reveal and draw")

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2
- P1>AnswerDecision:Leave

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION

---

# WhenPlayed_NoMatch
#// SOR_238 C-3PO — whiff: P1 chooses 5, but the top card SOR_095 costs 2 → no match. The player
#// STILL gets to look at the top card (the peek always happens — "Choose a number, THEN look at the
#// top card"), but the only outcome is to acknowledge and leave it on top: nothing is revealed or
#// drawn, and the card stays on top.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION
