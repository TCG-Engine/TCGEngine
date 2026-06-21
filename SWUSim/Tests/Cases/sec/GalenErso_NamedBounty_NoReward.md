# SEC_046 Galen Erso — naming a unit denies its Bounty. SHD_027 Hylobon Enforcer's "Bounty - Draw a
# card" should not be collectible. P1 names "Hylobon Enforcer", then defeats P2's SHD_027 (1/4) with an
# 8/8 (SOR_039). No bounty is offered — P1 draws nothing (deck stays full) and gets no bounty decision.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_039:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SHD_027:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hylobon Enforcer
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION
