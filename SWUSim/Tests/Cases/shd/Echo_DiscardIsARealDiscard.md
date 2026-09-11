# Echo_DiscardGoesThroughTheDiscardFunnel
#// SHD_099 Echo: "When Played: You may discard a card from your hand. Give 2 Experience tokens to a unit in
#// play with the same name as the discarded card." Found by the 2026-09-11 game-log pass: the handler moved
#// the card with a raw MZMove, skipping DoDiscardCard — so the discard entry had no From:HAND stamp and the
#// "when discarded" / "discarded from hand this phase" observers (LAW_206, LAW_179 / LAW_076 counters, SEC_016
#// Padmé, SHD_163 Migs) never saw it. (Fixture from shd/Echo_Restored.md.)

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
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:FROM:HAND
LOGCOUNT:1:discarded [[SOR_095
