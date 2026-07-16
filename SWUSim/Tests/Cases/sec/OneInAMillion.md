# CantPlayFromHand
#// SEC_053 One in a Million (Event, Plot, cost 1, Vigilance/Heroism)
#//   "This card can't be played from your hand. Defeat a unit with power and remaining HP both
#//    equal to the number of ready resources you control. Plot"
#// This test: the hand-play RESTRICTION. P1 has SEC_053 in hand, affords it (3 ready resources),
#// and the aspects are covered (bbw = Vigilance base + Luke Vig/Heroism leader) — so a NORMAL event
#// would play. SEC_053 must NOT: the play is a no-op, the card stays in hand, no cost paid, P1 keeps
#// its action. (The Plot-from-resources path is exercised by the other two cases.)

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_053

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# Plot_DefeatMatchingUnit
#// SEC_053 One in a Million — played via Plot, defeats a unit whose power AND remaining HP both
#// equal P1's ready-resource count at resolution.
#//
#// Setup: P1 controls SEC_053 as myResources-0 + 5 vanilla (6 ready → meets Luke's 6-resource deploy
#// threshold; SEC_053 costs 1). bbw = Vigilance base + Luke (Vig/Heroism) covers SEC_053's aspects.
#// After playing the cost-1 Plot card (it pays toward its own cost, like SEC_034), P1 has 5 ready
#// resources at resolution → N = 5.
#//   Enemy SOR_037 (5/5, undamaged)  → power 5, remaining HP 5  → VALID (the only legal target).
#//   Enemy SOR_046 (3/7, 2 damage)   → power 3, remaining HP 5  → NOT valid (HP matches, power doesn't).
#// The 5/5 is the sole valid target (auto-resolves); the 3/7 distractor survives — proving BOTH power
#// and remaining HP must equal N.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_037:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# Plot_NonMatchingHP_Fizzle
#// SEC_053 One in a Million — fizzle guard: the only unit matches power but NOT remaining HP.
#//
#// Same Plot setup as the positive case (N = 5 ready resources at resolution). The lone enemy is
#// SOR_037 (5/5) with 1 damage → power 5 (matches N), remaining HP 4 (does NOT match N). The "Defeat
#// a unit" is mandatory but has no legal target, so it fizzles cleanly: nothing is defeated, the unit
#// survives, and SEC_053 still resolves (event goes to discard). Proves remaining HP is checked, not
#// just power.

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
