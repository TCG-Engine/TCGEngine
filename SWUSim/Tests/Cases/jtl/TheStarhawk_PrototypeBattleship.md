# HalvesCost_PlaysExpensiveCheap
#// JTL_105 The Starhawk — While paying costs, you pay half as many resources, rounded up. P1 controls the
#// Starhawk and has only 2 ready resources, yet plays SOR_046 (printed cost 4, on-aspect via SOR_005's
#// Vigilance+Heroism). The halving makes it both AFFORDABLE (need ceil(4/2)=2) and PAID at 2 → 0 left.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0

---

# NoStarhawk_CannotAfford
#// Control for JTL_105: with NO Starhawk in play, 2 ready resources cannot pay SOR_046's printed cost 4,
#// so the play is rejected and SOR_046 stays in hand. (Proves the Starhawk's halving is what enables the
#// cheap play in Starhawk105_HalvesCost_PlaysExpensiveCheap.)

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# HalvesEventCost
#// JTL_105 The Starhawk halves the cost of EVENTS too (rounded up), not just units. P1 controls the
#// Starhawk with only 3 ready resources and plays Vanquish (TWI_077, printed cost 5, Vigilance on-aspect
#// via the b/bw base+leader) — halved to ceil(5/2)=3, so it is both affordable and paid at 3 → 0 left.
#// Vanquish defeats P2's Alliance X-Wing.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: TWI_077
WithP1SpaceArena: JTL_105:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1RESAVAILABLE:0

---

# DoesNotAffectOpponentCost
#// JTL_105 The Starhawk's discount applies only to ITS CONTROLLER's costs, not the opponent's. P1 controls
#// the Starhawk; P2 tries to play SOR_046 (printed cost 4) with only 2 ready resources. P2's cost is NOT
#// halved, so the play is rejected — SOR_046 stays in P2's hand and its resources are untouched.

## GIVEN
CommonSetup: bbw/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:2

---

# HalvesUpgradeCost
#// JTL_105 The Starhawk halves UPGRADE costs too. P1 controls the Starhawk (the only unit in the space
#// arena, so SOR_121's attach auto-resolves onto it) and plays Hardpoint Heavy Blaster (SOR_121, printed
#// cost 2, Command — on-aspect via ggw's Leia/Echo Base Command pip) with only 1 ready resource — halved
#// to ceil(2/2)=1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_121
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_105
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_121
P1RESAVAILABLE:0

---

# HalvesPilotingCost
#// JTL_105 The Starhawk halves the PILOTING cost of a unit played as a pilot upgrade, not just its unit
#// cost. JTL_100 Poe Dameron (unit cost 4, piloting cost 2, Command+Heroism — 0 penalty via ggw's Leia
#// SOR_009 + Echo Base) is played with only 1 ready resource while P1 controls a Starhawk (itself a
#// pilotable Vehicle, alongside SOR_237). Both the unit path (halved unit cost ceil(4/2)=2) and the pilot
#// path (halved piloting cost ceil(2/2)=1) are re-evaluated with halving, so canUnit=false (1<2) but
#// canPilot=true (1>=1, two eligible Vehicles) — the pilot-only short-circuit queues a Vehicle-choice
#// picker; P1 picks SOR_237.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 1
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:1:CARDID:JTL_105
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# AspectPenalty_PayCorrectCost
#// JTL_105 The Starhawk halves the cost AFTER the aspect penalty is added, and pays the correct (halved)
#// amount. P1 (base+leader bbk = Vigilance/Villainy) plays SHD_089 Pirate Battle Tank (printed cost 6,
#// Command+Villainy): Villainy matches, Command doesn't -> +2 penalty -> pre-halving cost 8 -> halved
#// ceil(8/2)=4. P1 has exactly 4 ready resources.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_089
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_089
P1RESAVAILABLE:0

---

# AspectPenalty_NotAllowIfTooHigh
#// Same as AspectPenalty_PayCorrectCost, but P1 has only 3 ready resources -- one short of the halved
#// post-penalty cost of 4. The play is rejected; SHD_089 stays in hand and resources are untouched.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_089
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:3

---

# HalvesLeaderAbilityActivationCost
#// JTL_105 The Starhawk halves ability ACTIVATION costs, including a leader's "Action [N resources,
#// Exhaust]:" cost -- not just play costs. Leia Organa (LAW_010, front Action [2 resources, Exhaust]:
#// give a unit +1/+1 per different aspect it has) costs 2 -- halved to ceil(2/2)=1 while P1 controls a
#// Starhawk. P1 has exactly 1 ready resource. Target SEC_080 (Command/Villainy = 2 aspects) gets +2/+2.

## GIVEN
CommonSetup: ygw/grw/{myLeader:LAW_010;myBase:SOR_028}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: JTL_105:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:0

---

# HalvesUnitAbilityActivationCost
#// JTL_105 The Starhawk halves a UNIT's "Action [N resources, Exhaust]:" activation cost too. Disaffected
#// Senator (TWI_157, "Action [2 resources, Exhaust]: Deal 2 damage to a base") costs 2 -- halved to
#// ceil(2/2)=1 while P1 controls a Starhawk. P1 has exactly 1 ready resource.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: TWI_157:1:0
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0

---

# DoesNotAffectOpponentLeaderAbilityCost
#// JTL_105 The Starhawk's activation-cost halving applies only to ITS CONTROLLER, not the opponent. P1
#// controls the Starhawk; P2 (leader Leia Organa, LAW_010, front Action [2 resources, Exhaust]) tries to
#// use the leader ability with only 1 ready resource -- one short of the UNHALVED cost of 2. The action is
#// unaffordable: the leader stays ready, the target is unbuffed, and P2's resource is untouched.

## GIVEN
CommonSetup: ygw/grw/{theirLeader:LAW_010;theirBase:SOR_028}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 1
WithP1SpaceArena: JTL_105:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
P2RESAVAILABLE:1

---

# DoesNotAffectOpponentUnitAbilityCost
#// JTL_105 The Starhawk's activation-cost halving applies only to ITS CONTROLLER, not the opponent. P1
#// controls the Starhawk; P2 controls Disaffected Senator (TWI_157, "Action [2 resources, Exhaust]: Deal 2
#// damage to a base") and tries to use it with only 1 ready resource -- one short of the UNHALVED cost of
#// 2. The action is unaffordable: the unit stays ready, no damage is dealt, and P2's resource is
#// untouched.

## GIVEN
CommonSetup: rrk/bbw/{theirResources:1}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_105:1:0
WithP2GroundArena: TWI_157:1:0

## WHEN
- P2>UseUnitAbility:myGroundArena-0

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:1

---

# AppliesToAllCardsWhileActive
#// JTL_105 The Starhawk's halving applies to EVERY card its controller plays while it's active, not just
#// one. P1 plays SOR_046 (unit, printed cost 4, on-aspect -> halved to 2) then Vanquish (TWI_077, event,
#// printed cost 5, on-aspect -> halved to 3) in the SAME phase. Total paid: 2 + 3 = 5.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [SOR_046 TWI_077]
WithP1SpaceArena: JTL_105:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2SPACEARENACOUNT:0
P1RESAVAILABLE:0

---

# CostReductionAfterCostIncrease
#// JTL_105 The Starhawk calculates its halving AFTER a cost INCREASE has been applied. P2 controls Qi'ra
#// (SHD_202) with SOR_046 named (her "each card with that name costs 3 resources more for your opponents
#// to play" -- seeded directly via the GlobalEffects flag her WhenPlayed would otherwise set). P1 controls
#// the Starhawk and plays SOR_046 (printed cost 4, on-aspect, so no aspect penalty): 4 + 3 (Qi'ra) = 7,
#// halved to ceil(7/2)=4. P1 has exactly 4 ready resources.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0
WithP2GroundArena: SHD_202:1:0
WithP2GlobalEffect: SWU_SHD202_NAMED|SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0

---

# CostReductionAfterCostDecrease
#// JTL_105 The Starhawk calculates its halving AFTER another cost DECREASE has been applied (GNK Power
#// Droid's SEC_110 "next unit you play this phase costs 1 resource less", seeded directly via the
#// GlobalEffects charge its On Attack would otherwise arm). P1 controls the Starhawk and plays SOR_046
#// (printed cost 4, on-aspect): 4 - 1 (SEC_110) = 3, halved to ceil(3/2)=2. P1 has 3 ready resources (1
#// spare left over after paying 2).

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0
WithP1GlobalEffect: SWU_SEC110_DISCOUNT_NEXT

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:1

---

# CostReductionAfterCostDecrease_ExactResources
#// Same stacking as CostReductionAfterCostDecrease, but P1 has EXACTLY the halved-after-decrease cost (2)
#// ready -- the play succeeds with 0 left.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0
WithP1GlobalEffect: SWU_SEC110_DISCOUNT_NEXT

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0

---

# CostReductionAfterCostDecrease_NotEnoughByOne
#// Same stacking as CostReductionAfterCostDecrease, but P1 has only 1 ready resource -- one short of the
#// halved-after-decrease cost of 2. The play is rejected; SOR_046 stays in hand and the resource is
#// untouched.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0
WithP1GlobalEffect: SWU_SEC110_DISCOUNT_NEXT

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# NotAffectOwnPlayCost_PayFull
#// JTL_105 The Starhawk's halving does NOT apply to its own play cost (it isn't in play yet while being
#// played, so it isn't counted by the halving check). P1 (ggw = Leia SOR_009 Command/Heroism + Echo Base
#// Command) fully covers JTL_105's Command+Heroism aspects -- 0 penalty. P1 has exactly 9 ready resources
#// (JTL_105's unhalved printed cost) and pays the full 9.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: JTL_105

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_105
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# NotAffectOwnPlayCost_CannotTargetIfNotEnough
#// Same as NotAffectOwnPlayCost_PayFull, but P1 has only 8 ready resources -- one short of JTL_105's
#// unhalved printed cost of 9 (it can't discount itself to make itself affordable). The play is rejected;
#// JTL_105 stays in hand and resources are untouched.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_105

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:8
