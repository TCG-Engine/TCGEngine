# SEC_148 Karis Nemik (Ground, 3/2, Aggression/Heroism) — Hidden (auto) + When Defeated: you may
#   disclose AggressionHeroism → create a Spy token and ready it.
# SEC_148 (3/2) attacks LAW_124 (4/7): takes 4, dies (LAW_124 survives). When Defeated: disclose
# SEC_153 (Aggression,Heroism → covers AggHeroism) → create a READY Spy token.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_148:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_153

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION
