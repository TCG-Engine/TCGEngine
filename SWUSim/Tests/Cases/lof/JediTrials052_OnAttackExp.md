# LOF_052 Jedi Trials — Attach to a Force unit; attached gains "On Attack: give an Experience token to
# this unit." Plo Koon (with Jedi Trials) attacks the base and gains an Experience token (2 subcards: the
# Trials upgrade + the new Experience).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_052

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
