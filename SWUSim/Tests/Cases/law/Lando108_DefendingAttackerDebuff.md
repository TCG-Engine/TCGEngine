# LAW_108 Lando Calrissian (4/5, Sentinel) — While this unit is defending, the attacker gets -1/-0.
# P2's SOR_046 (3 power) is forced to attack Lando and deals only 2 (3-1); Lando counters 4.

## GIVEN
CommonSetup: bgw/bgw/{}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1GroundArena: LAW_108:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_108
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4
