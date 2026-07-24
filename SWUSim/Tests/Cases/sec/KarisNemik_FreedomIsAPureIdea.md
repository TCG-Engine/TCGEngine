# DeclineDisclose_NoSpy
#// SEC_148 Karis Nemik — decline the When Defeated disclose → no Spy token created.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_148:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_153

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# WhenDefeated_Disclose_CreateReadySpy
#// SEC_148 Karis Nemik (Ground, 3/2, Aggression/Heroism) — Hidden (auto) + When Defeated: you may
#//   disclose AggressionHeroism → create a Spy token and ready it.
#// SEC_148 (3/2) attacks LAW_124 (4/7): takes 4, dies (LAW_124 survives). When Defeated: disclose
#// SEC_153 (Aggression,Heroism → covers AggHeroism) → create a READY Spy token.

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

---

# WhenDefeated_UnderEnemyControl_SpyGoesToNewController
#// SEC_148 Karis Nemik — When Defeated fires under whoever CONTROLS the unit at defeat.
#//   P2 plays JTL_043 (No Glory, Only Results): takes control of P1's Karis Nemik, then defeats it.
#//   Because P2 now controls Karis at the moment of defeat, P2 resolves the disclose and the READY
#//   Spy token is created on P2's side (disclosing SEC_153 from P2's hand for AggressionHeroism).

## GIVEN
CommonSetup: rrw/bbk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_148:1:0
WithP2Hand: JTL_043
WithP2Hand: SEC_153

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_T01
P2GROUNDARENAUNIT:0:READY
P2NODECISION
