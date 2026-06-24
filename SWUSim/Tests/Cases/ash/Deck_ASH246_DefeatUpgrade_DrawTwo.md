# ASH_246 Exploit Advantage (Event, cost 2) — Defeat a friendly upgrade. If you do, draw 2 cards. P1's
# SOR_095 carries SOR_120 (the only friendly upgrade, auto-resolved); playing Exploit Advantage defeats it
# and P1 draws 2. The upgrade leaves play (SOR_095 reverts to its 3/3 base power).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_246}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Deck: [SOR_046 SEC_080]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
