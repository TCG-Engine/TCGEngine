# ASH_042 Jabba the Hutt — declining the free replay leaves the returned upgrade in P1's hand. P1 returns
# its own SOR_120 (SOR_095 reverts to 3 power) but declines to replay it, so it stays in hand.
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1
