# SHD_226 Unrefusable Offer — the stolen unit carries SWU_SNEAK_DEFEAT: after P1 plays SOR_160 under
# its control via the bounty, the start of the regroup phase defeats it. Since P1 does not own SOR_160,
# the defeated unit goes to its OWNER's (P2's) discard, leaving P1 with only its original SOR_046.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:2
