# WhenPlayedCapturesNonLeader
#// TS26_027 Fortune and Glory (Unit 3/5 space, cost 4) — When Played: this unit captures a non-leader unit.
#// The only non-leader unit besides itself is the enemy SEC_080, which is captured (removed from the board).
## GIVEN
CommonSetup: gyk/rrk/{myResources:4;handCardIds:TS26_027}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:TS26_027
