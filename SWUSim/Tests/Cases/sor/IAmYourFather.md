# ControllerAllows_DealSeven
#// SOR_233 I Am Your Father — when the controller does NOT say "no" (answers NO to the refuse prompt),
#// the 7 damage is dealt. The target is a 4/7 wall, so 7 damage defeats it; the caster draws nothing.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ControllerRefuses_DrawThree
#// SOR_233 I Am Your Father (event, cost 3) — "Deal 7 damage to an enemy unit unless its controller
#// says 'no.' If they do, draw 3 cards." The single enemy unit auto-resolves as the target; its
#// controller (P2) says "no" (refuses the damage), so no damage is dealt and the CASTER draws 3.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:3
P1DISCARDCOUNT:1

---

# NoEnemyUnits_Fizzle
#// SOR_233 I Am Your Father — with no enemy unit to target, the event fizzles cleanly (no decision,
#// no draw) and goes to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1NODECISION
