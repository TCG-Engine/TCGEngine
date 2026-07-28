# OnAttackAggressionToBottomDealBases
#// LAW_174 0-0-0 (4/4) — On Attack: you may put an Aggression card from your discard on the bottom of
#// your deck. If you do, deal 1 to each enemy base. SOR_128 (Aggression) -> bottom; base takes 4 (combat)
#// + 1 (ability) = 5.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_128}
P1OnlyActions: true
WithP1GroundArena: LAW_174:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:5

---

# OnAttackDeclineNoBaseDamage
#// LAW_174 0-0-0 — the recycle-and-deal-1 ability is optional. Attack the base, then decline (Pass): the
#// discard stays intact (SOR_128 remains) and the enemy base only takes 4 (combat), no extra 1.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_128}
P1OnlyActions: true
WithP1GroundArena: LAW_174:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1DISCARDCOUNT:1
P2BASEDMG:4
