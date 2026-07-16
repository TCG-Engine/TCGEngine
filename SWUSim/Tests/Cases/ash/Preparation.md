# ExhaustHost
#// ASH_228 Preparation (Upgrade, cost 1) — When Played: exhaust attached unit. Played onto the ready
#// SOR_095, it exhausts it.
## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:ASH_228}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
