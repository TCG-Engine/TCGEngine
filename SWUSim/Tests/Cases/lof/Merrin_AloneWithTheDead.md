# DiscardDeal2
#// LOF_160 Merrin — On Attack: may discard a card from hand. If you do, deal 2 damage to a unit. Merrin
#// attacks the base, discards a card, and deals 2 to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DeclineDiscard_NoDamage
#// LOF_160 Merrin — "you MAY discard a card from hand. IF YOU DO, deal 2 damage." Declining the discard
#// gates the damage off: Merrin attacks the base, P1 keeps the card, and the enemy 3/7 is untouched.
## GIVEN
CommonSetup: rrk/ggw/{handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# EmptyHand_CannotDiscard_NoDamage
#// LOF_160 Merrin — with an EMPTY hand the discard cost cannot be paid at all, so the "if you do" damage
#// never happens and no dangling prompt is left. Distinct from declining: here there is nothing to offer.
## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
