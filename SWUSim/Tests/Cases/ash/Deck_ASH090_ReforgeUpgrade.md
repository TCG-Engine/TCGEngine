# ASH_090 Reforge (Event, cost 2) — Defeat an upgrade on a friendly unit, then search the top 8 for an
# upgrade that can attach to that unit and play it on that unit for 4 less. SOR_095 wears SOR_136 (the only
# upgrade, auto-defeated); the search finds SOR_120 (+2/+2) and plays it on SOR_095 for free → power 5.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_090}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Deck: [SOR_120 SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_120
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
