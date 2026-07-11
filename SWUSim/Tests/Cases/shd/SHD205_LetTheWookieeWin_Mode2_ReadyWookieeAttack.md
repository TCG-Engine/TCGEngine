# SHD_205 Let the Wookiee Win — the second mode: "You ready a friendly unit. If it's a Wookiee unit,
# attack with it. It gets +2/+0 for this attack." The opponent picks this mode; P1 readies the exhausted
# SHD_249 (Wookiee, 2 power), which attacks the base for 2 + 2 = 4.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_205
WithP1GroundArena: SHD_249:0:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:ReadyUnit
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
