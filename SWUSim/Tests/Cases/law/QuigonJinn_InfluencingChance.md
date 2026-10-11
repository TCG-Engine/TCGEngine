# OnAttackLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn (3/5, Sentinel) — When Played/On Attack: look at the top 3, you may discard 1,
#// put the rest back on top IN ANY ORDER. One REVEALARRANGE step ("ids|1": discard up to 1); the answer is
#// "keptTopFirst|discarded". Attacks the base; discards the top SOR_237, keeps the other two in deck order.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_046,SOR_095|SOR_237

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_237

---

# ReordersTheRestOnTop_AfterADiscard
#// LAW_237 Qui-Gon Jinn — ⚠ THE REPORTED BUG (owner, 2026-10-01: "not letting me choose the order to put back on
#// top"). The old flow offered only the optional discard and left the rest in deck order. Discard the MIDDLE card
#// (SOR_046) and put the BOTTOM one of the three (SOR_095) back on top — an order the deck did not already have.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: [SOR_237 SOR_046 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_095,SOR_237|SOR_046

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046

---

# ReordersAllThree_NoDiscard
#// LAW_237 Qui-Gon Jinn — the reorder is not tied to the discard: declining the discard ("you MAY discard 1")
#// still lets the player order all three. The third card goes on top.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: [SOR_237 SOR_046 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_095,SOR_046,SOR_237|

## EXPECT
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0

---

# OnAttackDiscardNothing
#// LAW_237 Qui-Gon Jinn — the discard is optional ("you may discard 1"). On Attack, P1 keeps all three in deck
#// order: nothing is milled.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_237,SOR_046,SOR_095|

## EXPECT
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_237
P1DISCARDCOUNT:0

---

# WhenPlayedLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn — the same look-top-3 fires When Played, not only On Attack. P1 plays Qui-Gon (cost 4)
#// from hand; the top card SOR_237 is discarded, the rest go back on top.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_237
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046,SOR_095|SOR_237

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_237
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# FewerThanThreeCards
#// LAW_237 Qui-Gon Jinn — with fewer than 3 cards in deck, only the available cards are looked at. Deck has a
#// single card; the offer is that one card (limit still 1); P1 discards it → deck empty, one card in discard.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:|SOR_237

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeckNoEffect
#// LAW_237 Qui-Gon Jinn — with an empty deck the look-at ability has nothing to reveal, so it resolves with no
#// effect and no decision. On Attack, deck stays empty and nothing is discarded.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1NODECISION

---

# LookNotDoubledByDeckSearchDoubler
#// LAW_237 Qui-Gon Jinn — "look at the top 3" is a LOOK-AT, not a deck SEARCH, so ASH_084 Arcana Star Map ("if
#// you would search a number of cards from your deck, search twice that many instead") attached to Qui-Gon must
#// NOT double it. With a 6-card deck the offer is exactly the top 3 cards, never 6, with a discard limit of 1.
#// Decision left PENDING to assert the offer (the REVEALARRANGE Param: "ids|MAX").
#// COVERAGE: offer=LookNotDoubledByDeckSearchDoubler (pending DECISIONPARAM over the top-3 pool) ·
#//           order=ReordersTheRestOnTop_AfterADiscard + ReordersAllThree_NoDiscard (the reported bug) ·
#//           limit=DevTools/tdd-regression/test_swusim_decision_validators.php (validator refuses a 2nd discard; finalize caps it; bridge never offers it — the harness's answer path runs the validator, so a refused answer cannot be asserted from here) · reqboundary=N/A (single-request resolution) ·
#//           control=N/A (own deck) · boundary=FewerThanThreeCards + EmptyDeckNoEffect · decline=OnAttackDiscardNothing

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: [SOR_237 SOR_046 SOR_095 SOR_128 SOR_164 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONPARAM:SOR_237,SOR_046,SOR_095|1

---

# LookPromptOffersTheCARDS_NotTheDeckPile
#// LAW_237 Qui-Gon Jinn — ⚠ THE PROMPT-RENDER CELL (live bug report #962: "prompt shows no cards, only the number
#// 42"). The look must offer the CARDS THEMSELVES, never the deck's own mzIDs (myDeck-N), which render as the
#// stacked `Deck` pile showing only its count. Since 2026-10-01 the offer is a REVEALARRANGE decision, whose client
#// panel (ShowRevealArrangePanel) draws each revealed card's art from the CardIDs in its Param — pinned here as
#// the pending decision's tooltip and Param.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: [SOR_237 SOR_046 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Discard up to 1 then put the rest back on top in any order
P1DECISIONPARAM:SOR_237,SOR_046,SOR_095|1
