# SHD_245 (2-cost 2/2 Heroism) — "When Played: Search the top 5 cards of your deck for an upgrade,
# reveal it, and draw it." Top of deck has an upgrade (SOR_069) among event fillers → drawn.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_245
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_245
P1HANDCOUNT:1
