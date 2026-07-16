# VsForceBuff
#// LOF_090 Inquisitor's Lightsaber (+1/+3) — attached gains "While attacking a Force unit, this unit gets
#// +2/+0." SOR_095 (3 base + 1 = 4) attacks the Force unit Plo Koon, getting +2 → deals 6.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_090
WithP2GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
