# SOR_215 on exhausted SOR_095: WhenPlayed fires, YES, but unit is exhausted — attack blocked.
# SOR_215 (Snapshot Reflexes, cost 1, Cunning) attached to SOR_095 (Battlefield Marine, 3/3).
# Unit starts exhausted (Status=0). Player answers YES to "attack?", but unit cannot attack.
# P2 base takes 0 damage. Unit remains exhausted.
# Thrawn+yellow base (yyk) covers Cunning → no aspect penalty.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:SOR_215}
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
