# NoCombatDamage
#// LAW_130 Betrayed Trust (Vigilance event, cost 2) — "Choose an enemy unit. For this phase, that unit
#// can't deal combat damage." Mark P2's SOR_046, then P1's SOR_095 attacks it: SOR_046 takes 3 but deals
#// NO counter-damage, so SOR_095 ends undamaged.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
