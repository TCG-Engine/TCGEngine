# SHD_166 Disabling Fang Fighter — Reprint fires same WhenPlayed
# SHD_166 shares the same closure as SOR_162 via stacked assignment.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SHD_166}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_166
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
