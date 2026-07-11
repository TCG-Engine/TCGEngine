# SHD_090 Maul — the redirected combat damage can DEFEAT the chosen unit. SOR_247 Underworld Thug (2/3)
# is pre-damaged 1 (2 remaining). Maul attacks Jabba (counter 2); redirected to the Thug → 1+2 = 3 ≥ 3 HP,
# so the Thug is defeated (to P1's discard) while Maul stays unharmed.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 SOR_247:1:1]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
