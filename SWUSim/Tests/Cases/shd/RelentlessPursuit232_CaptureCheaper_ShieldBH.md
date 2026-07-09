# SHD_232 Relentless Pursuit (3-cost event, Cunning) — "Choose a friendly unit. It captures an enemy
# non-leader unit that costs the same as or less than it. If the friendly unit is a Bounty Hunter, give a
# Shield token to it." LAW_124 (cost 4, Bounty Hunter) captures the enemy SEC_080 (cost 3) and gets a Shield.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
