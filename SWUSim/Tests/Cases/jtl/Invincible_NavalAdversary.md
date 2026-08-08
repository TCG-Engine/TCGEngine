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

---

# NonUniqueSeparatist_NoDiscount
#// JTL_191 Invincible — the discount requires a UNIQUE Separatist. Controlling only a NON-unique
#// Separatist (JTL_059 Corporate Defense Shuttle) does NOT satisfy it, so the cost-6 Invincible plays
#// for its full 6. With 6 resources, 6 → 0 left (a reduced cost of 5 would leave 1, so RESAVAILABLE:0
#// discriminates full cost).

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 6
WithP1SpaceArena: JTL_059:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:JTL_191
P1RESAVAILABLE:0

---

# OpponentSeparatist_NoDiscount
#// JTL_191 Invincible — the discount is a CONTROL check: "if YOU control a unique Separatist". The
#// opponent controlling the unique Separatist SOR_038 Count Dooku does not count, so the cost-6
#// Invincible plays for full 6 (6 resources → 0 left; a reduced 5 would leave 1).

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 6
WithP2GroundArena: SOR_038:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0

---

# NoSeparatist_NoDiscount
#// JTL_191 Invincible — with no Separatist in play at all (P1 controls only the non-Separatist SOR_095
#// Battlefield Marine), the discount does not apply and the cost-6 Invincible plays for full 6
#// (6 resources → 0 left; a reduced 5 would leave 1).

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0

---

# OpponentDeploysLeader_DoesNotTrigger
#// JTL_191 Invincible — "When YOU deploy a leader: you may return a non-leader unit that costs 3 or less
#// to its owner's hand." The trigger belongs to Invincible's controller only, so an OPPONENT deploying
#// their leader must not fire it. P1 controls Invincible and a cost-3 unit sits on P1's own board (the
#// unit Invincible would be able to bounce if it DID fire); P2 deploys. Nothing may be returned and no
#// decision may be raised for either player.
#// This is the actor-negative for the deploy hook — distinct from the existing absence guard, which
#// tests "no Invincible in play at all".

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  theirLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 8
WithP1SpaceArena: JTL_191:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION
P2NODECISION
