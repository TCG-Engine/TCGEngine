# SEC_179 Aggressive Negotiations (event, cost 3) — Attack with a unit. For this attack, it gets +1/+0
#   for each card in your hand. After SEC_179 leaves hand, 2 cards remain → SEC_041 (power 1) attacks
#   P2's base for 1+2 = 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_179
WithP1Hand: SEC_042
WithP1Hand: SEC_045

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
