# DealOne
#// LAW_206 That's a Rock (Aggression event, cost 1) — "Deal 1 damage to a unit." Single unit on board
#// (enemy SOR_046) -> auto-target -> 1 damage.

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
