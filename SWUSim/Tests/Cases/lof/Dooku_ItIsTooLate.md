# FriendlyHiddenCantBeAttacked
#// LOF_211 Dooku — Hidden + When Played: each friendly unit with Hidden can't be attacked for this phase.
#// P1 has a GIVEN-placed Hidden unit (LOF_228, normally attackable). Playing Dooku marks it can't-be-
#// attacked, so P2's attack finds no valid unit target and auto-redirects to P1's base.

## GIVEN
CommonSetup: yyk/yyw/{myResources:4;handCardIds:LOF_211}
WithActivePlayer: 1
WithP1GroundArena: LOF_228:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
