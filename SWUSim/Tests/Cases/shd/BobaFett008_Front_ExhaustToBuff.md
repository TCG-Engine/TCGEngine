# SHD_008 Boba Fett (front, undeployed) — "When you play a unit that has 1 or more keywords: You may
# exhaust this leader. If you do, give a friendly unit +1/+0 for this phase." P1 plays SOR_063 (Sentinel,
# keyword-only), accepts the reaction (exhausting Boba), and buffs its existing SOR_046 (3/7) to 4 power.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4
