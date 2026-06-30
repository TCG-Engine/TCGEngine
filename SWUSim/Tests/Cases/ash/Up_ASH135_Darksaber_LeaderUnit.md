# ASH_135 The Darksaber — "Attached unit is a leader unit." A friendly LAW_139 Admiral Motti ("friendly
# leader units get +2/+2") sees the Darksaber-wearing SOR_046 as a leader unit, so it gets the +2/+2 on
# top of its 7/9 (Darksaber stats) → 9/11. (Without the leader-unit grant it would stay 7/9.)
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1GroundArena: LAW_139:1:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:11
