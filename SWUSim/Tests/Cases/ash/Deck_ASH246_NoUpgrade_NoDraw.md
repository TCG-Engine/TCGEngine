# ASH_246 Exploit Advantage — the draw is gated on actually defeating an upgrade. With no friendly upgrade
# in play, the event fizzles cleanly: no defeat, no draw (the hand stays empty).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_246}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SEC_080]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
