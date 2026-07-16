# OnAttack_ExpToCreature
#// LOF_046 Ezra Bridger — On Attack: may give an Experience token to another Creature or Spectre unit.
#// Ezra attacks the base and gives an Experience token to the friendly Creature (Tuk'ata).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_046:1:0
WithP1GroundArena: LOF_161:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
