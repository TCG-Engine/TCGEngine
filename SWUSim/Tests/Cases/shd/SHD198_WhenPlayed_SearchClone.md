# SHD_198 (2-cost 2/2 Cunning/Heroism) — "When Played: Search the top 5 cards of your deck for a Clone
# card, reveal it, and draw it." Top of deck has a Clone card (SHD_095, Fringe/Clone) among fillers →
# drawn.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_198
WithP1Deck: SHD_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_095

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_198
P1HANDCOUNT:1
