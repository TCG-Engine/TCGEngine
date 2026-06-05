# SOR_227 Snowtrooper Lieutenant — the +2/+0 applies ONLY if the attacker is an Imperial
# unit. Here the attacker (Battlefield Marine, Rebel) is not Imperial, so it attacks the base
# for its base 3 with no buff. Guards the trait condition.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_095:1:0    # Rebel (NOT Imperial) attacker, 3/3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3
