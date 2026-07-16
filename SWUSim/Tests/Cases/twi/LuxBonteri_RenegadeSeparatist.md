# OpponentFreePlay_Triggers
#// TWI_210 Cunning — Exhaust branch: fires when opponent uses Exploit 1 (partial discount).
#// TWI_210 fires for ANY underpayment, not only maximum Exploit. This test covers:
#//   (a) Exploit with only 1 fodder defeated (cost 6 → pays 4, so 4 < 6 → triggers).
#//   (b) P1 chooses EXHAUST mode (exhausting a ready unit) instead of Ready.
#// Setup: P2 plays TWI_037 (cost 6, Exploit 2) but defeats only 1 of 2 available fodder
#//   → cost becomes 4. P1 resolves Cunning by exhausting their own ready TWI_210 at index 0.
#//
#// After: P2 ground arena has TWI_037 (idx 0) + surviving SEC_080 (idx 1).
#//        P1's TWI_210 (idx 0) is EXHAUSTED (was ready before, now exhausted by the reactive).
#//        P1's SOR_095 (idx 1) stays READY (untouched).
#//        P2 resources: 10 - 4 = 6 remaining.
#// P1: yyk = Administrator's Tower (Cunning) + Thrawn (Cunning+Villainy).
#// P2: bbk = Capital City (Vigilance) + Iden Versio (Vigilance+Villainy).

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

---

# OpponentPaidFull_NoTrigger
#// TWI_210 Cunning (Lux Bonteri) — reactive does NOT fire when the opponent pays the full
#// printed cost. P1 controls TWI_210 + SOR_095 EXHAUSTED (the target that should NOT be readied).
#// P2 plays SOR_095 (Battlefield Marine, cost 2, Command+Heroism) and pays 2 = full printed cost.
#// Condition "paid < printed cost" is FALSE → TWI_210 does not fire.
#// No AnswerDecision from P1 is needed; P2's play resolves with no reactive for P1.
#// After: SOR_095 (P2 index 0) is in P2's ground arena (exhausted, standard entry);
#//        P1's SOR_095 (index 1) remains EXHAUSTED — no ready applied.
#// P1: yyk = Administrator's Tower (Cunning) + Grand Admiral Thrawn (Cunning+Villainy).
#// P2: ggw = Echo Base (Command) + Leia Organa (Command+Heroism) — covers SOR_095 Heroism.

## GIVEN
CommonSetup: yyk/ggw/{theirResources:2;theirHandCardIds:SOR_095}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: TWI_210:1:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_210
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2RESAVAILABLE:0

---

# OpponentUnderpaid_ReadyOrExhaust
#// TWI_210 Cunning (Lux Bonteri, cost 2, Ground, Cunning) — reactive fires when opponent underpays.
#// P1 controls TWI_210 (ready) + SOR_095 EXHAUSTED (the unit P1 will choose to ready).
#// P2 plays TWI_037 Droideka Security (cost 6, Exploit 2, Villainy+Vigilance) using Exploit 2:
#//   defeats myGroundArena-0 (SEC_080) and myGroundArena-1 (SEC_080) → cost reduced 4 → pays 2.
#//   resourcesPaid=2 < printedCost=6 → TWI_210 reactive fires.
#// P1 resolves the reactive:
#//   OPTIONCHOOSE → picks "Ready"
#//   MZCHOOSE over all in-play units → picks myGroundArena-1 (the exhausted SOR_095)
#// After: SOR_095 (P1 index 1) should be READY.
#// P2 resources: 2 ready (pays 2 exactly for the exploited card; 0 remaining).
#// P1: yyk = Administrator's Tower (Cunning) + Grand Admiral Thrawn (Cunning+Villainy) — covers TWI_210 Cunning.
#// P2: bbk = Capital City (Vigilance) + Iden Versio (Vigilance+Villainy) — covers TWI_037 Villainy+Vigilance.

## GIVEN
CommonSetup: yyk/bbk/{theirResources:2;theirHandCardIds:TWI_037}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: TWI_210:1:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:Ready
- P1>AnswerDecision:Ready
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_210
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_037
P2RESAVAILABLE:0
