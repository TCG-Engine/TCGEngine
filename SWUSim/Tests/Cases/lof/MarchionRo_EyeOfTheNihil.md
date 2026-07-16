# RaidDoubled
#// LOF_186 Marchion Ro — Each friendly unit's Raid is doubled. LOF_136 (Raid 3, power 3) attacks the base:
#// its Raid is doubled to 6, so the base takes 3 + 6 = 9.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_136:1:0
WithP1GroundArena: LOF_186:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:9
