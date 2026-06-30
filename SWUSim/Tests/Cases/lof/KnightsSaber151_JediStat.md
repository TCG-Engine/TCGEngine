# LOF_151 Knight's Saber (+3/+2) — Attach to a Jedi non-Vehicle unit. P1 plays it onto Plo Koon (a Jedi),
# making him 9/10.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_151}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:10
