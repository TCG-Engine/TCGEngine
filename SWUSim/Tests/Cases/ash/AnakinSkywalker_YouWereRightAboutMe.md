# ShieldAnotherFriendly
#// ASH_255 Anakin Skywalker (Ground, 6/4, Hidden, Saboteur, cost 5) — When Played: give a Shield token to
#// another friendly unit. Playing Anakin shields SOR_095 (the only other friendly unit).
## GIVEN
CommonSetup: bbw/bbk/{myResources:5;handCardIds:ASH_255}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
