# SOR_016 Grand Admiral Thrawn — Leader Action: top deck card cost 1 (SOR_128), only unit in play costs 2 → no valid exhaust targets.
# Leader still exhausts and resource is spent; opponent's unit remains ready.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_128
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
