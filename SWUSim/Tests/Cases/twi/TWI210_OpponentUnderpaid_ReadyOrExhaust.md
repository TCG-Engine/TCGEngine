# TWI_210 Cunning (Lux Bonteri, cost 2, Ground, Cunning) — reactive fires when opponent underpays.
# P1 controls TWI_210 (ready) + SOR_095 EXHAUSTED (the unit P1 will choose to ready).
# P2 plays TWI_037 Droideka Security (cost 6, Exploit 2, Villainy+Vigilance) using Exploit 2:
#   defeats myGroundArena-0 (SEC_080) and myGroundArena-1 (SEC_080) → cost reduced 4 → pays 2.
#   resourcesPaid=2 < printedCost=6 → TWI_210 reactive fires.
# P1 resolves the reactive:
#   OPTIONCHOOSE → picks "Ready"
#   MZCHOOSE over all in-play units → picks myGroundArena-1 (the exhausted SOR_095)
# After: SOR_095 (P1 index 1) should be READY.
# P2 resources: 2 ready (pays 2 exactly for the exploited card; 0 remaining).
# P1: yyk = Administrator's Tower (Cunning) + Grand Admiral Thrawn (Cunning+Villainy) — covers TWI_210 Cunning.
# P2: bbk = Capital City (Vigilance) + Iden Versio (Vigilance+Villainy) — covers TWI_037 Villainy+Vigilance.

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
