# Raid: attacker's power increases by Raid value during the attack
# Leia Organa deployed unit (SOR_009, 3 power, Raid 1) attacks P2 base.
# Total power during attack = 3 + 1 = 4. Base takes 4 damage.

## GIVEN
CommonSetup: ggw/grw
WithP1GroundArena: SOR_009:1:0   # Leia Organa leader unit (3/6, Raid 1)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:0
