# DefenderDebuff
#// LOF_187 Corrupted Saber — if attached unit is a Force unit, it gains "On Attack: the defender gets
#// -2/-0 for this attack." Plo Koon (Force, with the saber) attacks the enemy 4/7, whose counter-power is
#// reduced from 4 to 2 → Plo Koon takes only 2.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_187
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
