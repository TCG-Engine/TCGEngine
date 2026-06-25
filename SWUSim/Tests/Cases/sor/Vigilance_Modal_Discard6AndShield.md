# SOR_058 Vigilance (event, cost 4) — "Choose two, in any order." P1 chooses Discard6 (mill 6 from the
# opponent's deck) then Shield (give a Shield to a unit; P1's lone unit auto-targets). The two modes
# resolve in sequence.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard6
- P1>AnswerDecision:Shield

## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:6
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
