# ASH_086 Durasteel Plating (Upgrade, cost 2) — When Played: give a Shield token to attached unit. Played
# onto SOR_095 (the only host), it gives SOR_095 a Shield.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2;handCardIds:ASH_086}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
