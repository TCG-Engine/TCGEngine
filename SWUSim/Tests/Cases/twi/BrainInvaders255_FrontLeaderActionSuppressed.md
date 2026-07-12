# TWI_255 Brain Invaders — a front (undeployed) leader's activated Action is an ability, so it is lost
# while a Brain Invaders is in play. P1's Luke Skywalker (SOR_005) tries to use his "Action [1 resource]"
# but it does nothing: the leader stays ready and the resource is not spent.
## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1RESAVAILABLE:1
