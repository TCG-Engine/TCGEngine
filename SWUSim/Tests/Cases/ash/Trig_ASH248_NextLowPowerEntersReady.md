# ASH_248 Neel (Ground, 1/4, cost 1) — When Played: the next unit you play this phase with 1 or less
# power enters play ready. P1 plays Neel (arming the effect), then plays ASH_073 (0 power), which enters
# play ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_248,ASH_073}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_073
P1GROUNDARENAUNIT:1:READY
