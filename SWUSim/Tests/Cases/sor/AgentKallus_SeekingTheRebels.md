# DeclineDraw
#// SOR_115 Agent Kallus — the draw is optional ("You may"): declining draws nothing. Kallus kills an
#// enemy unique unit, the reactive offers a draw, P1 says NO → no card drawn.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0

---

# NonUniqueDefeated_NoDraw
#// SOR_115 Agent Kallus — uniqueness gate: defeating a NON-unique unit does NOT trigger the draw.
#// Kallus (4/4) attacks a non-unique SOR_128 (3/1) and defeats it → no reactive, no draw, no decision.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# OncePerRound
#// SOR_115 Agent Kallus — "Use this ability only once each round." Two enemy UNIQUE units are defeated
#// in the same round; Kallus draws only for the FIRST. Kallus (4/4) kills SOR_079 (1/4) → draw (YES);
#// then LAW_124 (4/7) kills SOR_109 (2/3) → no second offer. P1 drew exactly 1 (deck 2 → 1).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_109:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:1

---

# UniqueDefeated_Draw
#// SOR_115 Agent Kallus — "When another unique unit is defeated: You may draw a card." Kallus (4/4)
#// attacks an enemy UNIQUE unit (SOR_079, 1/4) and defeats it → the reactive offers P1 a draw → YES →
#// P1 draws 1. (Kallus takes 1 counter, survives.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
