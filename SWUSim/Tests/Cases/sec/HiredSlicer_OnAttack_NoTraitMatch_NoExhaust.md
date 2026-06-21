# SEC_220 Hired Slicer — no trait match: when no unit in play shares a trait with either revealed card,
# the (optional) exhaust is not offered at all; the 2 cards are still bottomed. SEC_220 (Fringe) attacks
# alone; the revealed top 2 are SOR_095 (Rebel/Trooper). Fringe shares nothing with Rebel/Trooper, so no
# unit is eligible — no exhaust decision appears, the cards return to the bottom, and the attack resolves.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DECKCOUNT:2
P1NODECISION
