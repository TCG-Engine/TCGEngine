# 2ExpToImperial
#// SOR_231 TIE Advanced (Space, 3/2) — When Played: give 2 Experience tokens to
#// another friendly IMPERIAL unit. P1's Death Trooper (SOR_033, Imperial, 3/3) is the
#// only other Imperial → auto-receives +2/+2 (→ 5/5).

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_231}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
