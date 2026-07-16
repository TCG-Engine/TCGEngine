# SearchTop8_Mando_Upgrade
#// SHD_253 (2-cost Heroism event) — "Search the top 8 cards of your deck for up to 2 Mandalorian and/or
#// upgrade cards, reveal them, and draw them." Top of deck has one Mandalorian unit (SOR_142) and one
#// upgrade (SOR_069) among event fillers → both drawn; the 2 fillers go to the bottom.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_253
WithP1Deck: SOR_142
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_142,SOR_069

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:2
