# OppDiscardsCheap_CreatesSpy
#// SEC_178 Pursue the Lead (Event, cost 2) — "Choose a player. That player discards a card from their
#//   hand. If it costs 3 or less, create a Spy token." Choose Opponent; P2's only card SOR_095 (cost 2 ≤ 3)
#//   is discarded → create a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OppDiscardsExpensive_NoSpy
#// SEC_178 Pursue the Lead — the discarded card costs more than 3 → no Spy. P2's only card SEC_191
#//   (cost 5) is discarded; no Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178
WithP2Hand: SEC_191

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION

---

# OppDiscardsCostExactly3_CreatesSpy
#// SEC_178 Pursue the Lead — boundary: "costs 3 or less" INCLUDES 3. P2's only card SOR_126 (cost 3) is
#//   discarded → a Spy is created (exhausted) in P1's ground arena.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178
WithP2Hand: SOR_126

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# OppEmptyHand_NoDiscardNoSpy
#// SEC_178 Pursue the Lead — choosing an opponent with an empty hand discards nothing, so no Spy is
#//   created. Only Pursue the Lead itself ends up in P1's discard.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# SelfDiscard_InFlightEventNotSelectable
#// SEC_178 Pursue the Lead — choosing YOURSELF discards from your own hand, but the just-played Pursue
#//   the Lead has already left your hand (it is in your discard). With one other card (SOR_095, cost 2)
#//   left, that card is discarded and — cost ≤ 3 — a Spy is created. P1 discard holds both cards.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: [SEC_178 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1GROUNDARENACOUNT:1
P1NODECISION

---

# SelfDiscard_OnlyTheEventInHand_NothingToDiscard
#// SEC_178 Pursue the Lead — if Pursue the Lead was the ONLY card in hand, then after it leaves the hand
#//   there is nothing left to discard, so no card is discarded and no Spy is created (the in-flight event
#//   must NOT discard itself). Only Pursue the Lead ends up in P1's discard.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION


---

# BothHandsEmpty_NoDiscardEitherWay_NoSpy
#// SEC_178 Pursue the Lead — the both-empty boundary: P1's hand holds nothing but the event itself and
#// P2's hand is empty, so whichever player is chosen there is no card to discard and no Spy is created.
#// P1 chooses the opponent; both hands end empty, P2's discard stays empty, and only the spent event
#// sits in P1's discard.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
