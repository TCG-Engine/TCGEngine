# TWI_210 Cunning — Exhaust branch: fires when opponent uses Exploit 1 (partial discount).
# TWI_210 fires for ANY underpayment, not only maximum Exploit. This test covers:
#   (a) Exploit with only 1 fodder defeated (cost 6 → pays 4, so 4 < 6 → triggers).
#   (b) P1 chooses EXHAUST mode (exhausting a ready unit) instead of Ready.
# Setup: P2 plays TWI_037 (cost 6, Exploit 2) but defeats only 1 of 2 available fodder
#   → cost becomes 4. P1 resolves Cunning by exhausting their own ready TWI_210 at index 0.
#
# After: P2 ground arena has TWI_037 (idx 0) + surviving SEC_080 (idx 1).
#        P1's TWI_210 (idx 0) is EXHAUSTED (was ready before, now exhausted by the reactive).
#        P1's SOR_095 (idx 1) stays READY (untouched).
#        P2 resources: 10 - 4 = 6 remaining.
# P1: yyk = Administrator's Tower (Cunning) + Thrawn (Cunning+Villainy).
# P2: bbk = Capital City (Vigilance) + Iden Versio (Vigilance+Villainy).

## GIVEN
CommonSetup: yyk/bbk/{theirResources:10;theirHandCardIds:TWI_037}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: TWI_210:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_210
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:1:CARDID:TWI_037
P2RESAVAILABLE:6
