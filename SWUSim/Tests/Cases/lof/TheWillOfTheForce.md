# BounceAndDiscard
#// LOF_227 The Will of the Force — "Return a non-leader unit to its owner's hand. You may use the Force.
#// If you do, that player discards a random card." P1 bounces the enemy 3/7 (P2's only unit) to P2's hand,
#// then uses the Force; P2 now has exactly that one card and discards it at random.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# ReturnEnemy_DeclineForce
#// LOF_227 — return the enemy 3/7 to P2's hand but DECLINE the Force. No discard happens; P2 keeps the
#// bounced unit in hand and the Force token stays with P1.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1HASFORCE
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:0

---

# ReturnOwn_UseForce
#// LOF_227 — return P1's OWN unit to hand, then use the Force so P1 (the owner) discards a random card.
#// P1's hand held only the returned unit, so it is discarded and the hand empties.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# ReturnOwn_NoForceToken
#// LOF_227 — return P1's own unit to hand with NO Force token. The bounce still happens but nothing else;
#// the returned unit stays in hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# EmptyBoard_NoForce_DoesNothing
#// LOF_227 — empty board, no Force token. The event plays anyway and simply does nothing (no unit to return,
#// no Force to spend); it ends up in the discard.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0

---

# EmptyBoard_WithForce_KeepsToken_Intentional
#// LOF_227 The Will of the Force — RULING-consistent divergence. On an EMPTY board (no non-leader unit to
#// return) while holding the Force token, SWUSim does NOT offer the "you may use the Force" prompt (the
#// chained "that player discards" clause has no returned unit / no "that player" to target), so the token is
#// KEPT. This matches the project ruling to skip a no-effect Force-spend (same philosophy as Revan's
#// U_Trade_LeaderStaysReady). The event still resolves to discard. (Spending the Force for no effect is
#// intentionally not offered here.)
## GIVEN
CommonSetup: yyk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_227
WithP1Resources: 4
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1HASFORCE
