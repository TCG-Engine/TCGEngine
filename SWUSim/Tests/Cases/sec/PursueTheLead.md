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
