# TWI_255 Brain Invaders — control case for the front-action suppression: with NO Brain Invaders in
# play, Luke Skywalker's (SOR_005) front Action resolves normally and exhausts the leader. Confirms the
# suppression in the sibling test is caused by Brain Invaders.
## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
