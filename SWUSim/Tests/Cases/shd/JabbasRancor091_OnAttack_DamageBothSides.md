# SHD_091 Jabba's Rancor — the same "deal 3 to another friendly ground + 3 to an enemy ground" also fires
# On Attack. Proves the OnAttack-safe MZMAYCHOOSE path: Rancor attacks the base, the OnAttack rider damages
# SOR_046 (friendly) and SEC_080 (enemy) by 3 each.

## GIVEN
CommonSetup: grk/grk
P1OnlyActions: true
WithP1GroundArena: SHD_091:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
