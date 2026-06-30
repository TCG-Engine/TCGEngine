# LAW_152 C-3PO (1/4) — On Attack: you may give an Experience token to another non-leader unit that
# shares a Trait with a friendly leader. Leader Luke (Force,Rebel); SOR_095 (Rebel,Trooper) shares Rebel.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_152:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
