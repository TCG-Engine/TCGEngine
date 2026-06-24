# ASH_042 Jabba the Hutt — returning an ENEMY-owned upgrade sends it to the opponent's hand, and the free
# replay is NOT offered (the upgrade did not return to YOUR hand). P1 returns SOR_120 off the enemy SEC_080
# (which reverts to 3 power) and it lands in P2's hand.
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:1
