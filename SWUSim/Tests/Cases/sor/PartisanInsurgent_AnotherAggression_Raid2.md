# SOR_159 Partisan Insurgent (1/4) guard — "While you control another Aggression
# unit, this unit gains Raid 2." P1 controls Partisan Insurgent + another Aggression
# unit (SOR_130), so it has Raid 2. Attacking P2's base: power 1 + Raid 2 = 3 damage.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0    # Partisan Insurgent (1/4, Aggression)
WithP1GroundArena: SOR_130:1:0    # another Aggression unit

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
