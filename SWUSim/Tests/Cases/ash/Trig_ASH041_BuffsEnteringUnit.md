# ASH_041 Outcast (Space, 1/4) — When a friendly unit enters play (including this one): it gets +1/+0 for
# this phase. With ASH_041 in play, P1 plays SOR_095 (3/3); it enters at power 4.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_041:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
