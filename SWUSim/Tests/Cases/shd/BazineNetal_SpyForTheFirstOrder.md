# WhenPlayed_Decline
#// SHD_184 Bazine Netal — declining the optional discard: opponent's hand, discard, and deck are
#// untouched (and no draw happens).
#// COVERAGE: offer=WhenPlayed_DiscardOppDraws + WhenPlayed_Decline both answer out of a seeded 2-card
#//           enemy hand (the pool IS the opponent's whole hand, so there is no filter to assert with
#//           SELECTABLE); its negative bound is WhenPlayed_EmptyOpponentHand_NoPrompt ·
#//           decline=WhenPlayed_Decline (the '-' branch: no discard, no draw) ·
#//           boundary=WhenPlayed_DiscardOppDraws (2 cards in hand → a pick exists) vs
#//           WhenPlayed_EmptyOpponentHand_NoPrompt (0 cards → the ability is skipped) ·
#//           control=N/A (a one-shot When Played with no persistent effect, host or marker — nothing
#//           survives to change controller) · reqboundary=N/A (same reason: the whole ability resolves
#//           inside the play action, so there is no state to survive a serialization round-trip)

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_184
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2DECKCOUNT:1

---

# WhenPlayed_DiscardOppDraws
#// SHD_184 Bazine Netal (2-cost 1/3) — "When Played: Look at an opponent's hand. You may discard 1
#// of those cards. If you do, that player draws a card." P1 plays her, discards one of P2's two hand
#// cards → P2 draws: hand back to 2, discard 1, deck empty.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_184
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DECKCOUNT:0

---

# WhenPlayed_EmptyOpponentHand_NoPrompt
#// SHD_184 Bazine Netal — with the opponent holding NO cards, "Look at an opponent's hand. You may
#// discard 1 of those cards" has nothing to look at: the ability is skipped entirely (no decision), the
#// "If you do, that player draws a card" rider never fires, and the opponent's deck is untouched.
#// Intended: an empty enemy hand must not stall the play or hand out a free draw.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_184
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_184
P1NODECISION
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P2DECKCOUNT:1
