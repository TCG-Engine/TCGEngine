# TWI_255 Brain Invaders — control case: the leader-ability loss is conditional on a Brain Invaders being
# in play. With NO Brain Invaders on the board, a deployed SOR_003 keeps its Sentinel keyword. (Confirms
# the suppression in the sibling test is caused by Brain Invaders, not by the deploy itself.)
## GIVEN
CommonSetup: bbw/rrk/{myLeader:SOR_003;myLeaderDeployed:true}
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_003
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
