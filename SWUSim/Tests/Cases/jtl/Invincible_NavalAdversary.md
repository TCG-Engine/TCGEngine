# DeployLeader_CostFourNotEligible
#// JTL_191 Invincible — the bounce filter is "costs 3 or less". P2's only unit is the cost-4 SOR_046
#// Consular Security Force, which is NOT eligible, so deploying the leader offers no decision and the
#// unit is untouched. (Proves the ≤3 cutoff, distinguishing it from the ≤4 wording on JTL_223 Razor Crest.)

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P1NODECISION

---

# DeployLeader_Decline
#// JTL_191 Invincible — the deploy-leader bounce is a "may": declining leaves the eligible unit in play.
#// Same setup as the take test; P1 declines the MZMAYCHOOSE, so P2's SOR_063 stays and P2's hand is empty.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0

---

# DeployLeader_NoInvincible_NoTrigger
#// JTL_191 Invincible — absence guard: the deploy-leader bounce only fires while you control Invincible.
#// With no Invincible in play, deploying the leader offers no decision and P2's cost-3 unit is untouched.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P1NODECISION

---

# DeployLeader_ReturnsCheapUnit
#// JTL_191 Invincible — "When you deploy a leader: You may return a non-leader unit that costs 3 or
#// less to its owner's hand." P1 controls Invincible (space) and deploys its leader; the only ≤3
#// non-leader unit is P2's cost-3 SOR_063 Cloud City Wing Guard, which returns to P2's hand.
#// (SOR_015 is a non-pilot leader, so deploying with a friendly Vehicle present offers no Unit/Pilot choice.)

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# SeparatistLeader_CostMinus1
#// JTL_191 Invincible — the unique-Separatist discount is satisfied by a unique Separatist LEADER alone
#// (no units in play). P1's leader is JTL_014 Admiral Trench (Separatist, unique, undeployed). The cost-6
#// Invincible plays for 5, so 5 resources → 0 left. (Invincible is itself Separatist+unique, but the cost
#// check runs before it enters play and only counts units already in play, so it can't self-satisfy.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0

---

# UniqueSeparatist_CostMinus1
#// JTL_191 Invincible — If you control a unique Separatist card, this unit costs 1 resource less. With
#// the unique Separatist SOR_038 in play, the cost-6 Invincible plays for 5. (The "when you deploy a
#// leader" bounce rider is deferred.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 5
WithP1GroundArena: SOR_038:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0
