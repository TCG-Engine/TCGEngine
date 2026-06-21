# LAW_065 4-LOM (4/5) — When Played: you may attack with a friendly Bounty Hunter unit, even if it's
# exhausted. It can't attack bases this attack. Exhausted LAW_124 (Bounty Hunter, 4/7) attacks the enemy
# SOR_046 (3/7): deals 4, takes 3 counter.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3
