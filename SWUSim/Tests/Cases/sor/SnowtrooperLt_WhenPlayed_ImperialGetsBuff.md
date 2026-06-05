# SOR_227 Snowtrooper Lieutenant (2/2, Ground) — When Played: You may attack with a unit. If
# it's an Imperial unit, it gets +2/+0 for this attack. The chosen attacker (SOR_229, an
# Imperial 3/3) attacks the undefended enemy base for 3 + 2 = 5. The +2 is for THIS attack
# only, so the attacker's power is back to 3 afterward.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_229:1:0    # Imperial attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
