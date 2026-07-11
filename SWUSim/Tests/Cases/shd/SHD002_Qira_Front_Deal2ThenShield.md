# SHD_002 Qi'ra (front Action [1 resource, Exhaust]) — "Deal 2 damage to a friendly unit. Then, give a
# Shield token to it." SOR_046 (3/7) takes 2 and gets a Shield.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
