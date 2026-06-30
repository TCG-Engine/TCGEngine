# LAW_176 Sebulba's Podracer (3/3 Vehicle/Speeder) — "When you discard a card from your deck: You may
# ready this unit. Use this ability only once each round." LAW_173 BT-1 (index 1) attacks the base; its
# On Attack mills the top of P1's deck (a NON-Aggression card, so BT-1's own "if Aggression" rider adds
# no decision), which fires LAW_176's trigger. P1 answers YES and the exhausted Podracer readies.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_176:0:0
WithP1GroundArena: LAW_173:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:READY
