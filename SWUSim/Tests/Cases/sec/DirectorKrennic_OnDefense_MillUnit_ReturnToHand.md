# SEC_090 Director Krennic (Ground, 8/10, Command/Villainy) — Sentinel + On Defense (when this unit is
#   attacked): discard a card from your deck; if it's a unit, you may return it to your hand.
# P2 controls Krennic (defender). P1's SOR_046 (3/7) attacks it; before damage Krennic mills the top of
# P2's deck (SOR_095, a unit), and P2 chooses to return it to hand. Net: P2 deck 3→2, the milled unit is
# back in hand (P2 hand 1→2), discard stays 0. Combat then resolves: Krennic takes 3 (survives), counters
# 8 → SOR_046 (7 HP) is defeated.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2DECKCOUNT:2
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0
