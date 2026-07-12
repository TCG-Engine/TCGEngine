# TWI_255 Brain Invaders (Unit, 4/2, Ground) — "Each leader loses all abilities except for epic actions
# and can't gain abilities." A deployed SOR_003 (leader unit) normally has Sentinel; while an enemy Brain
# Invaders is in play, that keyword (an ability) is suppressed — the deployed leader has no Sentinel.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:SOR_003;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_003
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
