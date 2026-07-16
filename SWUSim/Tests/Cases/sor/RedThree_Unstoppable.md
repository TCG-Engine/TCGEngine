# BuffsOtherHeroismRaid
#// SOR_144 Red Three (2/3) — "Each other friendly [Heroism] unit gains Raid 1."
#// P1 controls Red Three + a Heroism unit (Consular Security Force SOR_046, Heroism,
#// power 3). With Red Three out, SOR_046 has Raid 1: attacking P2's base deals
#// 3 + 1 = 4 damage. (Red Three itself is not attacking; the grant excludes itself.)

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: SOR_144:1:0    # Red Three (2/3, Aggression/Heroism) — index 0
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7, Heroism) — index 1

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:4
