# NoHpDefeat
#// LOF_043 The Tragedy of Plagueis — Choose a friendly unit; this phase it can't be defeated by having no
#// remaining HP. An opponent chooses a unit they control; defeat it. P1 protects Plo Koon; P2 sacrifices
#// SOR_059; then Plo Koon attacks SOR_039 (8 power) and takes 8 lethal counter but SURVIVES at 0 HP.

## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_043}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:8
P2GROUNDARENACOUNT:1
