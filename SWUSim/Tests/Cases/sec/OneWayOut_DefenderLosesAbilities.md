# SEC_157 One Way Out — "If it attacks a unit, the defender loses all abilities for this attack." P1's
#   JTL_069 (4/7) attacks LOF_047 (3/4), whose On Defense ("when attacked, you may give it an Experience
#   token") would normally fire (a pending decision) and buff it to 4/5 so it survives. With One Way Out,
#   LOF_047 loses all abilities for this attack → its On Defense does NOT fire (P2NODECISION), so it stays
#   3/4 and is defeated by the 5 (4+1) attack. Overwhelm spills the 1 excess to P2's base.

## GIVEN
CommonSetup: rrw/grk/{myResources:1;handCardIds:SEC_157}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: LOF_047:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:1
P2NODECISION
