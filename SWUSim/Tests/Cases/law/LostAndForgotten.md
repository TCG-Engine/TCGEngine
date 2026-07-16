# DefeatHealBase
#// LAW_133 Lost and Forgotten (Vigilance event, cost 6) — "Defeat a non-leader unit. If you do, heal 3
#// damage from your base." Defeat P2's SEC_080 (single -> auto), heal 3 from P1 base (was at 3 -> 0).

## GIVEN
CommonSetup: bbw/bgw/{myResources:6;myBaseDamage:3}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_133

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
