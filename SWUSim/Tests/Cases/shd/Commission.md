# SearchTraitCard
#// SHD_127 Commission (1-cost event) — "Search the top 10 cards of your deck for a Bounty Hunter,
#// Item, or Transport card, reveal it, and draw it." Deck: two non-matching fillers + the Transport
#// (SHD_065 Vigilant Pursuit Craft, Vehicle/Transport). It's drawn; the rest go to the bottom.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SHD_065 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_065

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:1
