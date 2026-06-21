# SEC_053 One in a Million — fizzle guard: the only unit matches power but NOT remaining HP.
#
# Same Plot setup as the positive case (N = 5 ready resources at resolution). The lone enemy is
# SOR_037 (5/5) with 1 damage → power 5 (matches N), remaining HP 4 (does NOT match N). The "Defeat
# a unit" is mandatory but has no legal target, so it fizzles cleanly: nothing is defeated, the unit
# survives, and SEC_053 still resolves (event goes to discard). Proves remaining HP is checked, not
# just power.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_037:1:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_037
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
