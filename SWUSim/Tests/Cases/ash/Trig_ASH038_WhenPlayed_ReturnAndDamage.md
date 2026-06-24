# ASH_038 Purrgil Ultra (Space, 6/10, cost 8) — When Played: you may return another friendly non-leader
# unit to its owner's hand; if you do, deal damage to a unit equal to the returned unit's cost. P1 returns
# SEC_135 (cost 3) and deals 3 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: gyk/gyk/{myResources:8;handCardIds:ASH_038}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
