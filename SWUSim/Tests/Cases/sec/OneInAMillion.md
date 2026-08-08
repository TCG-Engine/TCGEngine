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

---

# Plot_MultipleValidTargets_ChooseAcrossArenas
#// SEC_053 One in a Million — "Defeat a unit with power and remaining HP both equal to the number of
#// ready resources you control." When more than one unit qualifies, the caster chooses (any arena, any
#// controller). Same Plot setup as the positive case: N = 5 ready resources at resolution.
#//   Enemy SOR_037 (5/5, ground) → power 5, HP 5 → VALID.
#//   Enemy SOR_050 (5/5, space)  → power 5, HP 5 → VALID.
#//   Enemy SOR_046 (3/7, 2 dmg)  → power 3, remaining HP 5 → NOT valid.
#// Two valid targets across both arenas → a choice is offered. P1 defeats the space unit; the ground
#// 5/5 and the 3/7 distractor both survive.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_037:1:0
WithP2GroundArena: SOR_046:1:2
WithP2SpaceArena: SOR_050:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1LEADER:DEPLOYED
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:2
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# PlayedFromDeckTopViaYoureMyOnlyHope
#// SEC_053 One in a Million — "can't be played from your HAND" restricts one zone only, so an effect
#// that plays it from another zone is legal. SOR_246 You're My Only Hope plays the top card of the deck
#// (5 less → free here). P1 pays 3 for You're My Only Hope, leaving 3 ready, and One in a Million then
#// defeats P2's SOR_095 Battlefield Marine — power 3 and remaining HP 3, both equal to those 3 ready
#// resources. Both events end in P1's discard.
#// Pairs with CantPlayFromHand: that section proves the restriction bites, this one proves it is scoped
#// to the hand rather than being a blanket "can never be played".

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_246
WithP1Deck: [SEC_053 SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:3
P1DISCARDCOUNT:2

---

# CantPlayFromHand_EvenWithAnEnemyRelentlessBlankingEvents
#// SEC_053 One in a Million — "can't be played from your hand" is a PLAY RESTRICTION, not an ability
#// that an opponent's blanking effect can strip. P2 controls SOR_089 Relentless ("the first event
#// played by each opponent each round loses all abilities"): the blanking would only apply once the
#// event were played, and the restriction stops it ever getting there. The card stays in P1's hand,
#// nothing is paid, and no unit is defeated even though P1's 3 ready resources match SOR_095's 3/3.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_053
WithP2SpaceArena: SOR_089:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:3
P2GROUNDARENACOUNT:1
P1NODECISION

---

# PlayedFromDISCARDViaAidFromTheInnocent
#// SEC_053 One in a Million — "This card CAN'T BE PLAYED FROM YOUR HAND" bans exactly one zone, so any
#// effect that grants a play from elsewhere works. TWI_201 Aid from the Innocent searches P1's top 10 for
#// 2 Heroism non-unit cards, discards them, and makes them playable this phase for 2 less; One in a
#// Million is a Heroism event, so it lands in P1's discard and is then played FROM THERE.
#// The numbers chain: 8 resources − 5 (Aid) = 3, then the event costs 1 (printed 1, +2 for the uncovered
#// Vigilance, −2 from Aid) leaving exactly 2 ready — and its effect defeats a unit whose power AND
#// remaining HP both equal that ready count, so P2's 2/2 SpecForce Soldier dies.
#// (Pairs with CantPlayFromHand: the ban is zone-specific, not a blanket "can't be played".)

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:TWI_201}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_053 SOR_199 SEC_080 SEC_080 SEC_080]
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_053,SOR_199
- P1>PlayFromDiscard:1

## EXPECT
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:2

---

# PlayedFromRESOURCESViaSmuggle
#// SEC_053 One in a Million — "This card can't be played from your hand" is a ZONE ban, so Smuggling it
#// out of the resource zone is legal. SHD_248 Tech gives friendly resources Smuggle at "that card's cost
#// plus 2 and its aspect icons", i.e. 3 here (1 + 2, on-aspect under a Vigilance/Heroism leader). P1
#// smuggles it with 6 resources, ending on 3 ready — and its effect defeats a unit whose power AND
#// remaining HP both equal that ready count, so P2's 3/3 SEC_080 dies.
#// Third distinct route onto the board for this card, alongside Plot and the play-from-discard section.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_248:1:0
WithP1Resources: 1:SEC_053:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>SmuggleResource:0

## EXPECT
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:3
P1RESCOUNT:6
P1NODECISION
