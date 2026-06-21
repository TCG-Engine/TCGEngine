# LAW_223 Rose Tico — guard: controlling only a UNIQUE unit (SOR_181 Jabba the Hutt) does NOT satisfy
# "a non-unique unit", so Rose enters EXHAUSTED (proves the rule is non-unique, not any-unit).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:EXHAUSTED
