# LAW_104 Bodhi Rook (2/4) — On Attack: you may give a friendly Rebel unit Sentinel for this phase.
# Attacks the base; grant Sentinel to the friendly SOR_095 (Rebel).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
