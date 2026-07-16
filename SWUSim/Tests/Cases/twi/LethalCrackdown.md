# DefeatUnit_SelfBaseDamage
#// TWI_041 Lethal Crackdown (Event, cost 4, Vigilance/Villainy, Tactic) — "Defeat a non-leader unit. Deal
#// damage to your base equal to that unit's power." The lone enemy SOR_095 (power 3) is defeated and P1's
#// base takes 3.

## GIVEN
CommonSetup: bbk/rrw/{myResources:4;handCardIds:TWI_041}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:3
