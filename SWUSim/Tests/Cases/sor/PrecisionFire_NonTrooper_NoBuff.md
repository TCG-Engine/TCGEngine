# SOR_168 Precision Fire — the +2/+0 is conditional on the attacker being a TROOPER. A non-Trooper
# attacker (LAW_124, 4/7, Underworld/Bounty Hunter) gets Saboteur but NO buff, so it deals only its
# base 4 to the enemy base.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:4
