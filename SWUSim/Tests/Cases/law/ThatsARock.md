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
