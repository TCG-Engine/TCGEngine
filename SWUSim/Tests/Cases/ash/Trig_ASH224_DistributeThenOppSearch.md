# ASH_224 Elzar Mann (Ground, 3/7, cost 6) — When Played: distribute up to 5 Advantage tokens among other
# friendly units; then an opponent searches twice that many cards from the top of their deck for an event,
# reveals it, and draws it. P1 gives SOR_095 2 Advantage, so P2 searches the top 4 and draws ASH_136 (event).
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_224}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_063 ASH_136 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2
- P2>AnswerDecision:ASH_136
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P2HANDCOUNT:1
