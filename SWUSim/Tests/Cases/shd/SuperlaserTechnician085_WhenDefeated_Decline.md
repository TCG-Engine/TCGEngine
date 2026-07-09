# SHD_085 Superlaser Technician — the "You may" is optional. Declining the When Defeated leaves the
# Technician in the discard; resources stay at 2.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_085:1:0
WithP1Resources: 2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_085
