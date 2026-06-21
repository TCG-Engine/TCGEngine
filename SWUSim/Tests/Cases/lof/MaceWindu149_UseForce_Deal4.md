# LOF_149 Mace Windu (6/6) — Overwhelm + When Played: may use the Force → deal 4 damage to a unit. P1
# uses the Force and deals 4 to the enemy 3/7.

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;handCardIds:LOF_149}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:4
