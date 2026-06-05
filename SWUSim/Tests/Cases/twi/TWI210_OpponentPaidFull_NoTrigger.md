# TWI_210 Cunning (Lux Bonteri) — reactive does NOT fire when the opponent pays the full
# printed cost. P1 controls TWI_210 + SOR_095 EXHAUSTED (the target that should NOT be readied).
# P2 plays SOR_095 (Battlefield Marine, cost 2, Command+Heroism) and pays 2 = full printed cost.
# Condition "paid < printed cost" is FALSE → TWI_210 does not fire.
# No AnswerDecision from P1 is needed; P2's play resolves with no reactive for P1.
# After: SOR_095 (P2 index 0) is in P2's ground arena (exhausted, standard entry);
#        P1's SOR_095 (index 1) remains EXHAUSTED — no ready applied.
# P1: yyk = Administrator's Tower (Cunning) + Grand Admiral Thrawn (Cunning+Villainy).
# P2: ggw = Echo Base (Command) + Leia Organa (Command+Heroism) — covers SOR_095 Heroism.

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
