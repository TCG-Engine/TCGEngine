# TWI_007 Captain Rex (Leader, front) — "Action [2 resources, Exhaust]: If a friendly unit attacked this
# phase, create a Clone Trooper token." SOR_095 attacks the base (a friendly attack this phase); then Rex's
# action creates a Clone.
## GIVEN
CommonSetup: rrk/bbw/{myResources:2;myLeader:TWI_007}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1RESAVAILABLE:0
