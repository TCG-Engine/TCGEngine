# DefeatDeclineReplay
#// ASH_247 One Must Destroy to Create — declining the optional replay leaves the defeated unit in the
#// discard pile. SOR_095 is defeated and P1 declines, so the arena is empty and the discard holds both the
#// event and SOR_095.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# DefeatThenReplayFree
#// ASH_247 One Must Destroy to Create (Event, cost 3) — Defeat a friendly non-leader unit, then you may
#// play that unit from your discard pile for free. SOR_095 (the only friendly non-leader unit, auto-chosen)
#// is defeated and replayed for free, so a fresh SOR_095 is back in the arena and the discard holds only the
#// event itself.
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

---

# SelfTargetAdvantage
#// ASH_247 One Must Destroy to Create defeats P1's own ASH_191 Shin Hati's Fiend Fighter (When Defeated:
#// may give 3 Advantage to a unit when NOT combat-defeated), then replays it from discard for free. Per
#// CR the event resolves FULLY (defeat + replay) before the triggered When Defeated resolves — so the
#// REPLAYED ASH_191 is back in the space arena and is a legal target for its own Advantage. Expected: the
#// replayed space unit ends with 3 Advantage tokens.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_247}
WithP1SpaceArena: ASH_191:1:0          # only friendly non-leader unit → auto-chosen for the defeat
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_191
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:3
