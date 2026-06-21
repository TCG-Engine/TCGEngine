# LAW_210 Salacious Crumb — fizzle/guard: with NO Jabba the Hutt controlled, Crumb enters play
# EXHAUSTED (CR 8.22.f default). Played at index 0.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED
