# UseForce_ExhaustEnemyDraw
#// LOF_218 Impossible Escape — "You may either exhaust a friendly unit OR use the Force. If you do either,
#// exhaust an enemy unit and draw a card." P1 pays via the Force, then exhausts the enemy unit and draws.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_218}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:UseForce

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1

---

# ExhaustFriendly_ThenExhaustEnemyAndDraw
#// LOF_218 Impossible Escape — the cost can be paid by exhausting a friendly unit (instead of the Force).
#// P1 exhausts SOR_095, then exhausts the enemy SOR_046 and draws a card.
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ExhaustFriendly
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1

---

# PayForce_NoEnemy_JustDraws
#// LOF_218 — paying the cost with no enemy unit in play still draws a card (the enemy-exhaust is skipped).
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:UseForce
## EXPECT
P1NOFORCE
P1HANDCOUNT:1

---

# ExhaustFriendly_NoEnemy_JustDraws
#// LOF_218 — pay by exhausting a friendly unit with NO enemy unit in play: the enemy-exhaust step is
#// skipped but P1 still draws a card. Intended: "can exhaust a friendly unit to draw a card".
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ExhaustFriendly
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1

---

# EmptyBoard_NoForce_NoOp
#// LOF_218 — on an empty board with no Force token, neither payment option is available, so the event
#// plays to no effect (no draw). Intended: "can be used to do nothing".
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1NOFORCE

---

# NoForce_ChooseForce_NoEffect
#// LOF_218 — with NO Force token, choosing the "use the Force" option cannot pay the cost, so nothing
#// happens: no enemy exhaust, no draw. Intended: "can choose to use the Force to do nothing".
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:UseForce
## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1HANDCOUNT:0

---

# HasForce_NoFriendly_ChooseExhaust_NoEffect
#// LOF_218 — with a Force token but NO friendly unit, choosing "exhaust a friendly unit" cannot pay the
#// cost, so nothing happens: the Force is kept, no enemy exhaust, no draw. Intended: "can choose to exhaust a
#// friendly unit to do nothing".
## GIVEN
CommonSetup: yyk/ggw/{myResources:3;handCardIds:LOF_218}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ExhaustFriendly
## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:READY
P1HANDCOUNT:0
