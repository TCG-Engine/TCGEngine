# LAW_034 Chewbacca (4/4, Overwhelm) — When Attack Ends: if the defending unit was defeated, give it an
# Experience token and heal 3 from him. Attacks SOR_128 (3/1, dies); Chewbacca takes 3 then heals 3
# (DAMAGE:0) and gains Experience (5/5).

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:5
