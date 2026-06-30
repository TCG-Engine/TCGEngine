# LOF_051 Jedi Holocron — Attach to a Force unit; attached gains "On Attack: may heal 3 from another
# unit." Plo Koon (with the Holocron) attacks the base and heals 3 from the damaged friendly SOR_046
# (5 → 2).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_051
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
