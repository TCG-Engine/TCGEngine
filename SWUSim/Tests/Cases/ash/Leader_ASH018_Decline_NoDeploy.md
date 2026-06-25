# ASH_018 Grogu — declining the optional deploy leaves Grogu undeployed. P1 plays ASH_109 (unique, cost 4)
# and declines to deploy.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_109
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1LEADER:NOTDEPLOYED
