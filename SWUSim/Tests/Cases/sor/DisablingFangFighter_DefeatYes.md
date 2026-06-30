# SOR_162 Disabling Fang Fighter — DefeatYes
# Only P2's unit has an upgrade; exactly one, so both unit and upgrade are
# auto-resolved after YES. Token (SOR_T01) is set aside — not discarded.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_162
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:0
