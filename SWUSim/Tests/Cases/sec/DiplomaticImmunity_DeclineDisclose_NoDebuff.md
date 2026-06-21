# SEC_052 Diplomatic Immunity — the granted On Defense disclose is OPTIONAL. P2 declines
#   (AnswerDecision:-), so the attacker keeps its full power 3. Host (5/9) takes 3 (DAMAGE:3) and
#   counters 5 onto the attacker. Proves the decline path no-ops cleanly and the upgrade seam still
#   pauses combat correctly even when the reaction is declined.

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:3
