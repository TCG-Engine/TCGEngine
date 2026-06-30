# ASH_247 One Must Destroy to Create (Event, cost 3) — Defeat a friendly non-leader unit, then you may
# play that unit from your discard pile for free. SOR_095 (the only friendly non-leader unit, auto-chosen)
# is defeated and replayed for free, so a fresh SOR_095 is back in the arena and the discard holds only the
# event itself.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
