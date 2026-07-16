# GrantsRebelAndAura
#// LAW_150 Fulcrum (Upgrade, +2/+2) — "Attached unit gains the Rebel trait and 'Each other friendly
#// Rebel unit gets +2/+2.'" Two Imperial SEC_080s each wear a Fulcrum: each becomes Rebel (grant) and
#// each gets +2/+2 from the OTHER's Fulcrum aura. So each = 3/3 base + own Fulcrum (2/2) + other Fulcrum
#// aura (2/2) = 7/7. (Without the Rebel grant, an Imperial wouldn't receive the aura → only 5/5.)

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_150
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:LAW_150

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
