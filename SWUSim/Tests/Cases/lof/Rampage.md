# CreatureBuff
#// LOF_127 Rampage — Each friendly Creature unit gets +2/+2 for this phase. LOF_063 (Creature, 5/5) becomes
#// 7/7; the non-Creature Plo Koon is unaffected.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_127}
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:6
