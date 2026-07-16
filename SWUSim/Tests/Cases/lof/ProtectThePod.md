# DealRemainingHP
#// LOF_128 Protect the Pod — A friendly non-Vehicle unit deals damage equal to its REMAINING HP to an enemy
#// unit. Plo Koon (8 HP, already 3 damage → 5 remaining) deals 5 to SOR_046 (3/7), which survives with 5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_128}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
