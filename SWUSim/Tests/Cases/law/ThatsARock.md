# DealOne
#// LAW_206 That's a Rock (Aggression event, cost 1) — "Deal 1 damage to a unit." Single unit on board
#// (enemy SOR_046) -> auto-target -> 1 damage.
#// COVERAGE: offer=ChooseAmongManyUnits (all four units in both arenas are legal; the pick lands on the
#//           chosen one) · reqboundary=DiscardedFromHandDealsOne + DiscardedFromDeckDealsOne (the
#//           may-deal-1 pends across a request after the discard) · control=N/A (no control-change
#//           interaction intended; the trigger belongs to the card's owner) · boundary
#//           pair=DiscardedFromHandDealsOne vs DiscardedFromDeckDealsOne (both discard sources fire; a
#//           normal PLAY not firing the rider is guarded in the engine and asserted by DealOne ending
#//           with no extra prompt) · decline=DiscardedFromDeckDealsOne declines the follow-up offer;
#//           a decline of the may-deal-1 itself is open pending the two self-chosen/return-path
#//           discard gaps noted in the session report

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_206

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DiscardedFromHandDealsOne
#// LAW_206 That's a Rock — "When this event is discarded from your hand or deck: You may deal 1 damage
#// to a unit." LAW_204 forces P1 to discard LAW_206 from hand, triggering its may-deal-1.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP1Hand: LAW_206
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# ChooseAmongManyUnits
#// LAW_206 That's a Rock — "Deal 1 damage to a unit." With units in BOTH arenas on BOTH sides, all four
#// are legal targets; choose the enemy SOR_164 Wampa for 1 damage.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_029:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_132:1:0
WithP1Hand: LAW_206

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DiscardedFromDeckDealsOne
#// LAW_206 That's a Rock — "When this event is discarded from your hand or deck: You may deal 1 damage to
#// a unit." LAW_203 Daring Delve mills LAW_206 from P1's deck, triggering its may-deal-1; deal it to the
#// enemy SOR_164 Wampa. (Daring Delve's return-an-Aggression offer is declined afterward.)

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Deck: LAW_206
WithP1Deck: SOR_095
WithP1Hand: LAW_203
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# SelfChosenHandDiscard_TriggersTheRock
#// "When this event is discarded from your hand or deck" must fire on a SELF-CHOSEN discard too, not
#// just forced ones: SEC_197 Furtive Handmaiden's On Attack "you may discard a card from your hand"
#// discards the Rock; its may-deal-1 then prompts (two enemy units seeded so the offer is a real
#// choice) and resolves for 1 on the Wampa. The draw half of the Handmaiden also resolves.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_197:1:0
WithP1Hand: LAW_206
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# DiscardedFromHand_InteractsWithPadmeLeaderPrompt
#// Two discard-observers in one window: SEC_197 Furtive Handmaiden discards the Rock while SEC_016
#// Padmé Amidala (leader, undeployed) is also watching hand-discards. Both reactions resolve — Padmé
#// exhausts to deal 1, the Rock's own trigger deals 1 — onto the enemy Consular (2 total), and the
#// Handmaiden's draw still happens.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:SEC_016:1}
P1OnlyActions: true
WithP1GroundArena: SEC_197:1:0
WithP1Hand: LAW_206
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SEC_167:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
P1HANDCOUNT:1
