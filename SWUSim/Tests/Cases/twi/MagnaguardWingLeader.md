# Action_TwoDroidAttacks
#// TWI_082 MagnaGuard Wing Leader (Unit, Space, Command/Villainy) — "Action: Attack with a Droid unit.
#// Then, attack with another Droid unit. Use this ability only once each round." Two Battle Droids (TWI_T01,
#// 1/1) each attack the enemy base for 1 (total 2).

## GIVEN
CommonSetup: ggk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:2
