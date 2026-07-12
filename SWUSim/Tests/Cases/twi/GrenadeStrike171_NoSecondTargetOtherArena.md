# TWI_171 Grenade Strike — same-arena restriction: the first target is the only ground unit; the only
# other unit is in the SPACE arena, so no second-hit offer is made (no pending decision) and the space
# unit is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:0
