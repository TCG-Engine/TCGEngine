# RevealEventDeal1
#// SOR_176 ISB Agent (cost 1) — When Played: you may reveal an event from your hand;
#// if you do, deal 1 to a unit. P1's hand has an event (Open Fire, SOR_172) to reveal.
#// Answering YES reveals it and deals 1 to the chosen enemy (Battlefield Marine).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
