# JTL_047 Admiral Yularen grants Shielded to friendly Vehicles, THEN JTL_130 Timely Reinforcements
# creates X-Wing tokens (JTL_T02, Vehicles). The opponent controls 8 resources → 4 X-Wings. Because
# Yularen grants Shielded to Vehicles, each token gains Shielded and — since it's entering play — must
# enter WITH a Shield token (Shielded applies on creation, not just when "played").
# gbw aspects cover JTL_047 (Vigilance/Heroism, cost 3) and JTL_130 (Command, cost 5) with no penalty.

## GIVEN
CommonSetup: gbw/grw/{myResources:8;theirResources:8}
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Hand: JTL_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:2:SHIELDCOUNT:1
P1SPACEARENAUNIT:3:SHIELDCOUNT:1
