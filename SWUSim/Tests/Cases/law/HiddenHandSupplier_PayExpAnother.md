# LAW_257 Hidden Hand Supplier (cost 1) — When Played: you may pay 1 resource. If you do, give an
# Experience token to another unit. Pay 1; the only other unit (SOR_095) auto-targets.

## GIVEN
CommonSetup: bgw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
