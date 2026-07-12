# TWI_255 Brain Invaders — the ability loss is "except for epic actions." A leader's Epic deploy is an
# epic action, so it still works while a Brain Invaders is in play: P1 deploys Luke Skywalker (SOR_005,
# cost 6) normally. (After deploying, the leader UNIT would have its abilities suppressed — covered by
# the deployed-keyword test — but the deploy itself is unaffected.)
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
