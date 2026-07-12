# TWI_150 Saw Gerrera (Unit 4/8, Ground, cost 6, Aggression/Heroism, Fringe/Trooper) — Raid 2 + "On
# Attack: If your base has 15 or more damage on it, deal 1 damage to each enemy ground unit." With P1's
# base at 15 damage, attacking the enemy base deals 1 to each enemy ground unit (SOR_095 survives at 1,
# SOR_128 3/1 dies), then combat deals power 4 + Raid 2 = 6 to the enemy base.

## GIVEN
CommonSetup: rrw/bbw/{myBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: TWI_150:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:6
