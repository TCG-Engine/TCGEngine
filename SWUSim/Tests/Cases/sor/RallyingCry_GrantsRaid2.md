# SOR_154 Rallying Cry (Event, cost 3) — "Each friendly unit gains Raid 2 this
# phase." After playing it, P1's Battlefield Marine (power 3) attacks P2's base
# with Raid 2: 3 + 2 = 5 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
