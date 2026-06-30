# SOR_215 on ready SOR_095: WhenPlayed fires, YES, unit attacks P2 base — base takes 4 damage.
# SOR_215 (Snapshot Reflexes, cost 1, Cunning) attached to SOR_095 (Battlefield Marine, 3/3).
# SOR_215 gives +1 power → Marine attacks at 4 power. P2 has no units → single target (base).
# Single target auto-fires ExecuteSWUAttack without MZCHOOSE.
# Thrawn+yellow base (yyk) covers Cunning → no aspect penalty. 1 resource exhausted.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:SOR_215}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
