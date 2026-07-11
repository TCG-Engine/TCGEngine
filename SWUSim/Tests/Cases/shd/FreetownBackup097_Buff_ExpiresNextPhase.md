# SHD_097 Freetown Backup — the +2/+2 is "for this phase": gone after the regroup.

## GIVEN
CommonSetup: gbw/gbw
P1OnlyActions: true
WithP1GroundArena: SHD_097:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
