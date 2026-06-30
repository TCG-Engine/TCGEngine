# ASH_065 Home One (Space, 7/10, Sentinel) — When Played: heal all damage from each friendly unit. P1's
# SOR_095 (2 damage) and SOR_237 (1 damage) are both fully healed when Home One enters.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_065}
WithP1GroundArena: SOR_095:1:2
WithP1SpaceArena: SOR_237:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
