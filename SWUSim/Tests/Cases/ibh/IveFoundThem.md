# DrawUnitDiscardRest
#// IBH_009 I've Found Them (Event, cost 2, Command) — Reveal the top 3 of your deck, draw a unit revealed
#//   this way, then discard the other revealed cards. Top 3 = a Unit + 2 events; the Unit is drawn, the
#//   two events are discarded (NOT bottomed).

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:3
P1NODECISION

---

# NoUnit_DiscardsAll
#// IBH_009 I've Found Them — if no Unit is in the top 3, you draw nothing and discard all 3 revealed
#//   cards. (Player confirms none with an empty AnswerDecision.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:4
P1NODECISION

---

# Reprint025
#// IBH_025 I've Found Them (reprint of IBH_009) — reveal top 3, draw a unit, discard the rest. Confirms
#//   the duplicate.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_025
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:3
P1NODECISION

---

# FewerThanThreeCardsInDeck_RevealsOnlyWhatIsThere
#// IBH_009 I've Found Them — "reveal the top 3" clamps to the deck size. With only 2 cards left (a Unit
#// and an event) both are revealed, the Unit is drawn and the event is discarded; the deck empties
#// without error. Discard = the played event + the 1 unchosen reveal = 2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION

---

# EmptyDeck_NothingRevealedAndNothingDrawn
#// IBH_009 I've Found Them — played on an EMPTY deck it resolves cleanly as a no-op: nothing is
#// revealed, nothing is drawn, and the only card in the discard is the event itself. No dangling
#// decision is left behind.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
