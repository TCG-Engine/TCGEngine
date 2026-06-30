# LOF_111 Maz Kanata — When Played: may attack with a Force unit. It gets +2/+0 for this attack. P1's
# ready Plo Koon (6 power) attacks the base buffed to 8.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_111}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:8
