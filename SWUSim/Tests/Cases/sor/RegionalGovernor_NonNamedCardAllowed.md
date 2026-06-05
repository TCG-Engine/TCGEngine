# SOR_062 Regional Governor — the block is name-specific. P1 names "Death Star Stormtrooper"
# (SOR_128, which P2 doesn't have). On P2's turn, P2 plays a DIFFERENT card — Battlefield Marine
# (SOR_095) — which is NOT the named card, so it plays normally.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Death Star Stormtrooper
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2HANDCOUNT:0
P2RESAVAILABLE:0
