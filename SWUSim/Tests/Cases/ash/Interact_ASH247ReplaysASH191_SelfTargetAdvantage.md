# ASH_247 One Must Destroy to Create defeats P1's own ASH_191 Shin Hati's Fiend Fighter (When Defeated:
# may give 3 Advantage to a unit when NOT combat-defeated), then replays it from discard for free. Per
# CR the event resolves FULLY (defeat + replay) before the triggered When Defeated resolves — so the
# REPLAYED ASH_191 is back in the space arena and is a legal target for its own Advantage. Expected: the
# replayed space unit ends with 3 Advantage tokens.
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
