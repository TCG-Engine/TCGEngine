# WhenPlayed_AttackWithHost
#// SHD_223 Snapshot Reflexes (1-cost +1/+1 upgrade) — "When Played: You may attack with attached unit."
#// (Reprint of SOR_215.) Attaching it to the ready SOR_095 (now 4/4) and choosing to attack sends it at the
#// base for 4.

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_223
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
