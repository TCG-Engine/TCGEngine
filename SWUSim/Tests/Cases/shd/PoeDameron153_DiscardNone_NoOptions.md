# SHD_153 Poe Dameron — discard 0 cards → no options are offered (the modal is only driven by the
# discard count). Poe still deals its 6 combat damage; the hand is kept and the base takes no extra.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_153:1:0
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2BASEDMG:0
P1HANDCOUNT:2
P1NODECISION
