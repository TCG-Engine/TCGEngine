# LOF_078 Whirlwind of Power — Give a unit -2/-2 for this phase; if you control a Force unit, -3/-3
# instead. P1 controls Plo Koon (Force), so the enemy SOR_046 (3/7) gets -3/-3 → 0/4.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4
