# AttackBaseSelfDefeat
#// LAW_205 Flash the Vents (Aggression event, cost 1) — "Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, if that unit damaged a base, defeat that
#// unit." SEC_080 (power 3) attacks the base for 3+2 = 5, then self-defeats.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
