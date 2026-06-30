# LAW_059 Highsinger (4/2) — When Defeated: give an Experience token to a friendly Aggression unit.
# Highsinger attacks SOR_046 (3/7) and dies (takes 3 vs 2 HP); SOR_128 (Aggression) gets the Experience.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_059:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
