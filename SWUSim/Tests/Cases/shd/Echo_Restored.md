# WhenPlayed_DiscardNameMatch_Give2Exp
#// SHD_099 Echo (4-cost, Command/Heroism) — Restore 2 + "When Played: You may discard a card from your hand.
#// Give 2 Experience tokens to a unit in play with the same name as the discarded card." P1 discards the
#// Battlefield Marine (SOR_095) from hand; the in-play SOR_095 (same name) gets 2 Experience (→ 5/5).

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_099
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1DISCARDCOUNT:1
