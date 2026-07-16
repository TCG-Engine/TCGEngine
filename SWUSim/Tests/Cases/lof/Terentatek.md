# OpponentForce_Ambush
#// LOF_118 Terentatek (5/5) — "While an opponent controls a Force unit, this unit gains Ambush." With the
#// enemy Plo Koon (a Force unit) in play, it has Ambush; otherwise it does not.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_118:1:0
WithP2GroundArena: LOF_050:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
