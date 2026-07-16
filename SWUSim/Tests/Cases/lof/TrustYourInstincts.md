# UseForce_BuffedAttack
#// LOF_221 Trust Your Instincts — "Use the Force. If you do, attack with a unit. It gets +2/+0 for this
#// attack and deals combat damage before the defender." P1's 3/3 attacks the base buffed to 5 power.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_221}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2BASEDMG:5
