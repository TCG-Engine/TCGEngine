# 145_AsUpgrade_Pay2ReadyResistance
#// JTL_145 BB-8 (pilot) — When played as an upgrade: you may pay 2 resources; if you do, ready a
#// Resistance unit. Played onto SOR_237, P1 pays 2 and readies the exhausted Resistance unit JTL_109.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_145
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: JTL_109:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# AsUpgrade_DeclinePay_NoReady
#// JTL_145 BB-8 — the "pay 2 to ready a Resistance unit" is optional. Declining leaves the resources unspent
#// and the exhausted Resistance unit JTL_109 stays exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_145
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: JTL_109:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AsUpgrade_ReadyDeployedLeader
#// JTL_145 BB-8 — the "ready a Resistance unit" may target a DEPLOYED leader unit (an undeployed leader is
#// not on the board and cannot be chosen). Kazuda Xiono (JTL_018, Resistance) is deployed and exhausted;
#// BB-8 is played as a Pilot onto the lone friendly Vehicle (SOR_237), P1 pays 2, and the only exhausted
#// Resistance unit — the deployed leader — is readied.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_018:0:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_145
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:JTL_018
P1GROUNDARENAUNIT:0:READY
