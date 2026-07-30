# WhenPlayed_SearchTop8ForAnUpgrade_DrawIt
#// HMW_085 Remote Scout (1/3, Vigilance, cost 2) — "When Played: Search the top 8 cards of your deck for an
#// upgrade, reveal it, and draw it. (Put the other cards on the bottom in a random order.)" One upgrade
#// (SOR_120 Academy Training) sits among unit fillers in the top 8; it is drawn (hand +1, deck -1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_120 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_120

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_085
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# WhenPlayed_NoUpgradeInTop8_DrawsNothingCardsReturn
#// No upgrade among the top 8 (all unit fillers). The search still presents (the player looks at the top 8),
#// but nothing is drawable — choosing none (empty AnswerDecision) draws nothing and returns all peeked cards
#// to the bottom, so the deck count is unchanged and no card is milled.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_085
P1HANDCOUNT:0
P1DECKCOUNT:10
