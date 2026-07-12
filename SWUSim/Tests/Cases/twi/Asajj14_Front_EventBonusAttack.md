# TWI_014 Asajj Ventress (Leader, front) — "Action [Exhaust]: Attack with a unit. If you played an event
# this phase, it gets +1/+0 for this attack." Playing TWI_173 (an event) first, then attacking with SOR_095
# (3 → 4 power) deals 4 to the base.
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myLeader:TWI_014;handCardIds:TWI_173}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:4
